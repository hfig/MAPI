<?php

namespace Hfig\MAPI\Property;

use Hfig\MAPI\OLE\CompoundDocumentElement as Element;
use Hfig\MAPI\OLE\Guid\OleGuid;
use Hfig\MAPI\OLE\Time\OleTime;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PropertyStore
{
    public const SUBSTG_RX     = '/^__substg1\.0_([0-9A-F]{4})([0-9A-F]{4})(?:-([0-9A-F]{8}))?$/';
    public const PROPERTIES_RX = '/^__properties_version1\.0$/';
    public const NAMEID_RX     = '/^__nameid_version1\.0$/';

    public const VALID_RX = [
        self::SUBSTG_RX,
        self::PROPERTIES_RX,
        self::NAMEID_RX,
    ];

    protected PropertyCollection $cache;
    protected $nameId;

    protected LoggerInterface $logger;

    public function __construct(?Element $obj = null, protected $parentNameId = null, ?LoggerInterface $logger = null)
    {
        $this->cache  = new PropertyCollection();
        $this->logger = $logger ?? new NullLogger();

        if ($obj instanceof Element) {
            $this->load($obj);
        }
    }

    protected function load(Element $obj): void
    {
        // # find name_id first
        foreach ($obj->getChildren() as $child) {
            if (preg_match(self::NAMEID_RX, (string) $child->getName())) {
                $this->nameId = $this->parseNameId($child);
            }
        }
        if (is_null($this->nameId)) {
            $this->nameId = $this->parentNameId;
        }

        foreach ($obj->getChildren() as $child) {
            if ($child->isFile()) {
                if (preg_match(self::PROPERTIES_RX, (string) $child->getName())) {
                    $this->parseProperties($child);
                } elseif (preg_match(self::SUBSTG_RX, (string) $child->getName(), $matches)) {
                    $key      = hexdec($matches[1]);
                    $encoding = hexdec($matches[2]);
                    $offset   = hexdec($matches[3] ?? '0');

                    $this->parseSubstg($key, $encoding, $offset, $child);
                }
            }
        }
    }

    /**
     * @return array<PropertyKey>
     */
    protected function parseNameId($obj): array
    {
        // $remaining = clone $obj->getChildren()

        $knownPpsAlias = [
            'guids' => '__substg1.0_00020102',
            'props' => '__substg1.0_00030102',
            'names' => '__substg1.0_00040102'];

        $knownPpsObj = array_combine(
            array_keys($knownPpsAlias),
            [null, null, null],
        );

        foreach ($obj->getChildren() as $child) {
            $alias = array_search($child->getName(), $knownPpsAlias);
            if ($alias !== false) {
                $knownPpsObj[$alias] = $child;
            }
        }

        // # parse guids
        // # this is the guids for named properities (other than builtin ones)
        // # i think PS_PUBLIC_STRINGS, and PS_MAPI are builtin.
        // # Scan using an ascii pattern - it's binary data we're looking
        // # at, so we don't want to look for unicode characters
        $guids   = [PropertySetConstants::PS_PUBLIC_STRINGS()];
        $rawGuid = str_split((string) $knownPpsObj['guids']->getData(), 16);
        foreach ($rawGuid as $guid) {
            if (strlen($guid) == 16) {
                $guids[] = OleGuid::fromBytes($guid);
            }
        }

        // # parse names.
        // # the string ids for named properties
        // # they are no longer parsed, as they're referred to by offset not
        // # index. they are simply sequentially packed, as a long, giving
        // # the string length, then padding to 4 byte multiple, and repeat.
        $namesData = $knownPpsObj['names']->getData();

        // # parse actual props.
        // # not sure about any of this stuff really.
        // # should flip a few bits in the real msg, to get a better understanding of how this works.
        // # Scan using an ascii pattern - it's binary data we're looking
        // # at, so we don't want to look for unicode characters
        $propsData  = $knownPpsObj['props']->getData();
        $properties = [];
        foreach (str_split((string) $propsData, 8) as $idx => $rawProp) {
            if (strlen($rawProp) < 8) {
                break;
            }

            $d      = unpack('vflags/voffset', substr($rawProp, 4));
            $flags  = $d['flags'];
            $offset = $d['offset'];

            // # the property will be serialised as this pseudo property, mapping it to this named property
            $pseudo_prop = 0x8000 + $offset;
            $named       = ($flags & 1) === 1;
            if ($named) {
                $str_off = unpack('V', $rawProp)[1];
                if (strlen((string) $namesData) - $str_off < 4) {
                    continue;
                } // not sure with this, but at least it will not read outside the bounds and crash
                $len  = unpack('V', substr((string) $namesData, $str_off, 4))[1];
                $data = substr((string) $namesData, $str_off + 4, $len);
                $prop = mb_convert_encoding($data, 'UTF-8', 'UTF-16LE');
            } else {
                $d = unpack('va/vb', $rawProp);
                if ($d['b'] != 0) {
                    $this->logger->Debug('b not 0');
                }
                $prop = $d['a'];
            }

            // # a bit sus
            $guid_off = $flags >> 1;
            $guid     = $guids[$guid_off - 2];

            /*$properties[] = [
                'key' => new PropertyKey($prop, $guid),
                'prop' => $pseudo_prop,
            ];*/
            $properties[$pseudo_prop] = new PropertyKey($prop, $guid);
        }

        // # this leaves a bunch of other unknown chunks of data with completely unknown meaning.
        // #	pp [:unknown, child.name, child.data.unpack('H*')[0].scan(/.{16}/m)]
        // print_r($properties);
        return $properties;
    }

    protected function parseSubstg($key, $encoding, $offset, Element $obj): void
    {
        $MULTIVAL = 0x1000;

        if (($encoding & $MULTIVAL) != 0) {
            if (!$offset) {
                // # there is typically one with no offset first, whose data is a series of numbers
                // # equal to the lengths of all the sub parts. gives an implied array size i suppose.
                // # maybe you can initialize the array at this time. the sizes are the same as all the
                // # ole object sizes anyway, its to pre-allocate i suppose.
                // #p obj.data.unpack('V*')
                // # ignore this one
                return;
            }

            // remove multivalue flag for individual pieces
            $encoding &= ~$MULTIVAL;
        } else {
            if ($offset) {
                $this->logger->warning(sprintf('offset specified for non-multivalue encoding %s', $obj->getName()));
            }
            $offset = null;
        }

        $valueFn = PropertyStoreEncodings::decodeFunction($encoding, $obj);

        // $property = [
        //    'key' => $key,
        //    'value' => $valueFn,
        //    'offset' => $offset
        // ];

        $this->addProperty($key, $valueFn, $offset);
    }

    // # For parsing the +properties+ file. Smaller properties are serialized in one chunk,
    // # such as longs, bools, times etc. The parsing has problems.
    protected function parseProperties($obj): void
    {
        $data = $obj->getData();
        $pad  = $obj->getSize() % 16;

        // # don't really understand this that well...
        // it's also wrong
        // if (!(($pad == 0 || $pad == 8) && substr($data, 0, $pad) == str_repeat("\0", 16))) {
        //    $this->logger->warning('padding was not as expected', ['pad' => $pad, 'size' => $obj->getSize(), substr($data, 0, $pad)]);
        // }

        // # Scan using an ascii pattern - it's binary data we're looking
        // # at, so we don't want to look for unicode characters
        foreach (str_split(substr((string) $data, $pad), 16) as $idx => $rawProp) {
            // copying ruby implementation's oddness to avoid any endianess issues
            $rawData               = unpack('V', $rawProp)[1];
            [$property, $encoding] = str_split(sprintf('%08x', $rawData), 4);
            $key                   = hexdec($property);

            // # doesn't make any sense to me. probably because its a serialization of some internal
            // # outlook structure..
            if ($property === '0000') {
                continue;
            }

            // improved from ruby-msg - handle more types
            // https://docs.microsoft.com/en-us/office/client-developer/outlook/mapi/property-types
            switch ($encoding) {
                case '0001':    // PT_NULL
                    break;

                case '0002':    // PT_I2
                case '1002':    // PT_MV_I2
                    $value = unpack('v', substr($rawProp, 8, 2))[1];
                    $this->addProperty($key, $value);
                    break;

                case '0003':    // PT_I4
                case '1003':    // PT_MV_I4
                    $value = unpack('V', substr($rawProp, 8, 4))[1];
                    $this->addProperty($key, $value);
                    break;

                case '0004':    // PT_FLOAT
                case '1004':    // PT_MV_FLOAT
                    $value = unpack('f', substr($rawProp, 8, 4))[1];
                    $this->addProperty($key, $value);
                    break;

                case '0005':    // PT_DOUBLE
                case '1005':    // PT_MV_DOUBLE
                    $value = unpack('e', substr($rawProp, 8, 8))[1];
                    $this->addProperty($key, $value);
                    break;

                case '0006':    // PT_CURRENCY
                case '1006':    // PT_MV_CURRENCY
                    // TODO work out how to interpret PT_CURRENCY (same as VB currency type, apparently)
                    $value = unpack('a8', substr($rawProp, 8, 8))[1];
                    $this->addProperty($key, $value);
                    break;

                case '0007':    // PT_APPTIME
                case '1007':    // PT_MV_APPTIME
                    // TODO work out how to interpret PT_APPTIME (same as VB time type, apparently)
                    $value = unpack('a8', substr($rawProp, 8, 8))[1];
                    $this->addProperty($key, $value);
                    break;

                case '000a':    // PT_ERROR
                    $value = unpack('V', substr($rawProp, 8, 4))[1];
                    $this->addProperty($key, $value);
                    break;

                case '000b':    // PT_BOOLEAN
                case '100b':    // PT_MV_12
                    // Windows 2-byte BOOL
                    $value = unpack('v', substr($rawProp, 8, 2))[1];
                    $this->addProperty($key, $value != 0);
                    break;

                case '000d':    // PT_OBJECT
                    // pointer to IUnknown - cannot exist in an Outlook property hopefully!!
                    break;

                case '0014':    // PT_I8
                case '1014':    // PT_MV_I8
                    // $value = unpack('P', substr($rawProp, 8, 8))[1];
                    // raw data, change endianess
                    $raw   = strrev(substr($rawProp, 8, 8));
                    $value = ord($raw[7]);
                    for ($i = 6; $i >= 0; --$i) {
                        $fig   = (string) ord($raw[$i]);
                        $order = (string) abs(8 - $i);
                        $value = bcadd($value, bcmul($fig, bcmul('10', $order)));
                    }
                    $this->addProperty($key, $value);
                    break;

                case '001e':    // PT_STRING8
                case '101e':    // PT_MV_STRING8
                    // LPSTR - stored in a stream
                    // $value = substr($rawProp, 8);
                    // $this->addProperty($key, $value);
                    break;

                case '001f':    // PT_TSTRING
                case '101f':    // PT_MV_TSTRING
                    //  LPWSTR - stored in a stream
                    // $value = substr($rawProp, 8);
                    // $this->addProperty($key, $value);
                    break;

                case '0040':    // PT_SYSTIME
                case '1040':    // PT_MV_SYSTIME
                    $value = OleTime::getTimeFromOleTime(substr($rawProp, 8));
                    $this->addProperty($key, $value);
                    break;

                case '0048':    // PT_CLSID
                    $value = (string) OleGuid::fromBytes($rawProp);
                    $this->addProperty($key, $value);
                    break;

                case '1048':    // PT_MV_CLSID
                    $value = (string) OleGuid::fromBytes(substr($rawProp, 8));
                    $this->addProperty($key, $value);
                    break;

                case '00fb':    // PT_SVREID
                    // Variable size, a 16-bit (2-byte) COUNT followed by a structure.
                    break;

                case '00fd':    // PT_SRESTRICT
                    // Variable size, a byte array representing one or more Restriction structures.
                    break;

                case '00fe':    // PT_ACTIONS
                    // Variable size, a 16-bit (2-byte) COUNT of actions (not bytes) followed by that many Rule Action structures.
                    break;

                case '0102':    // PT_BINARY
                case '1102':    // PT_MV_BINARY
                    // assume this is also stored in a stream
                    // $value = substr($rawProp, 8);
                    // $this->addProperty($key, $value);
                    break;

                default:
                    $this->logger->warning(sprintf('ignoring data in __properties section, encoding: %s', $encoding), unpack('H*', $rawProp));
            }
        }
    }

    protected function addProperty($key, $value, $pos = null): void
    {
        // # map keys in the named property range through nameid
        if (is_int($key) && $key >= 0x8000) {
            if (!$this->nameId) {
                $this->logger->warning('No nameid section yet named properties used');
                $key = new PropertyKey($key);
            } elseif (isset($this->nameId[$key])) {
                $key = $this->nameId[$key];
            } else {
                // # i think i hit these when i have a named property, in the PS_MAPI
                // # guid
                $this->logger->warning(sprintf('property in named range not in nameid %s', print_r($key, true)));
                $key = new PropertyKey($key);
            }
        } else {
            $key = new PropertyKey($key);
        }

        // $this->logger->debug(sprintf('Writing property %s', print_r($key, true)));
        // $hash = $key->getHash();
        if (!is_null($pos)) {
            if (!$this->cache->has($key)) {
                $this->cache->set($key, []);
            }
            if (!is_array($this->cache->get($key))) {
                $this->logger->warning('Duplicate property');
            }

            $el       = $this->cache->get($key);
            $el[$pos] = $value;
            $this->cache->set($key, $el);
        } else {
            $this->cache->set($key, $value);
        }
    }

    public function getCollection(): PropertyCollection
    {
        return $this->cache;
    }

    public function getNameId()
    {
        return $this->nameId;
    }
}
