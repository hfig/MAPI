<?php

namespace Hfig\MAPI\Property;

use Hfig\MAPI\OLE\Guid\OleGuid;
use Ramsey\Uuid\UuidInterface as OleGuidInterface;

// ruby-msg Mapi::PropertySet

class PropertySetConstants
{
    // the property set guid constants
    // these guids are all defined with the macro DEFINE_OLEGUID in mapiguid.h.
    // see http://doc.ddart.net/msdn/header/include/mapiguid.h.html

    public const NAMES = [
        '00020328' => 'PS_MAPI',
        '00020329' => 'PS_PUBLIC_STRINGS',
        '00020380' => 'PS_ROUTING_EMAIL_ADDRESSES',
        '00020381' => 'PS_ROUTING_ADDRTYPE',
        '00020382' => 'PS_ROUTING_DISPLAY_NAME',
        '00020383' => 'PS_ROUTING_ENTRYID',
        '00020384' => 'PS_ROUTING_SEARCH_KEY',
        // string properties in this namespace automatically get added to the internet headers
        '00020386' => 'PS_INTERNET_HEADERS',
        // theres are bunch of outlook ones i think
        // http://blogs.msdn.com/stephen_griffin/archive/2006/05/10/outlook-2007-beta-documentation-notification-based-indexing-support.aspx
        // IPM.Appointment
        '00062002' => 'PSETID_Appointment',
        // IPM.Task
        '00062003' => 'PSETID_Task',
        // used for IPM.Contact
        '00062004' => 'PSETID_Address',
        '00062008' => 'PSETID_Common',
        // didn't find a source for this name. it is for IPM.StickyNote
        '0006200e' => 'PSETID_Note',
        // for IPM.Activity. also called the journal?
        '0006200a' => 'PSETID_Log',
    ];

    private const OLE_GUID = '{${prefix}-0000-0000-c000-000000000046}';

    protected static function get(string $offset): OleGuidInterface
    {
        static $lookup = [];
        if (isset($lookup[$offset])) {
            return $lookup[$offset];
        }

        $guid = array_search($offset, static::NAMES);
        if ($guid === false) {
            throw new \RuntimeException(sprintf('offset %s not found', $offset));
        }

        $guid = str_replace('${prefix}', $guid, self::OLE_GUID);
        $guid = OleGuid::fromString($guid);

        $lookup[$offset] = $guid;

        return $guid;
    }

    public static function PS_MAPI(): OleGuidInterface
    {
        return self::get('PS_MAPI');
    }

    public static function PS_PUBLIC_STRINGS(): OleGuidInterface
    {
        return self::get('PS_PUBLIC_STRINGS');
    }

    public static function PS_ROUTING_EMAIL_ADDRESSES(): OleGuidInterface
    {
        return self::get('PS_ROUTING_EMAIL_ADDRESSES');
    }

    public static function PS_ROUTING_ADDRTYPE(): OleGuidInterface
    {
        return self::get('PS_ROUTING_ADDRTYPE');
    }

    public static function PS_ROUTING_DISPLAY_NAME(): OleGuidInterface
    {
        return self::get('PS_ROUTING_DISPLAY_NAME');
    }

    public static function PS_ROUTING_ENTRYID(): OleGuidInterface
    {
        return self::get('PS_ROUTING_ENTRYID');
    }

    public static function PS_ROUTING_SEARCH_KEY(): OleGuidInterface
    {
        return self::get('PS_ROUTING_SEARCH_KEY');
    }

    public static function PS_INTERNET_HEADERS(): OleGuidInterface
    {
        return self::get('PS_INTERNET_HEADERS');
    }

    public static function PSETID_Appointment(): OleGuidInterface
    {
        return self::get('PSETID_Appointment');
    }

    public static function PSETID_Task(): OleGuidInterface
    {
        return self::get('PSETID_Task');
    }

    public static function PSETID_Address(): OleGuidInterface
    {
        return self::get('PSETID_Address');
    }

    public static function PSETID_Common(): OleGuidInterface
    {
        return self::get('PSETID_Common');
    }

    public static function PSETID_Note(): OleGuidInterface
    {
        return self::get('PSETID_Note');
    }

    public static function PSETID_Log(): OleGuidInterface
    {
        return self::get('PSETID_Log');
    }
}
