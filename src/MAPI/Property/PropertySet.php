<?php

namespace Hfig\MAPI\Property;

use Symfony\Component\Yaml\Yaml;

class PropertySet implements \ArrayAccess
{
    const SCHEMA_DIR = __DIR__ . '/../Schema';

    /** @var PropertyStore */
    private $store;

    /** @var PropertyCollection */
    private $raw;

    private static $tagsMsg;
    private static $tagsOther;
    private $map = [];


    public function __construct(PropertyStore $store)
    {
        $this->store = $store;
        $this->raw = $store->getCollection();

        if (!self::$tagsMsg || !self::$tagsOther) {
            self::init();
        }

        $this->map();
    
    }

    private static function init()
    {
        self::$tagsMsg   = Yaml::parseFile(self::SCHEMA_DIR . '/MapiFieldsMessage.yaml');
        self::$tagsOther = Yaml::parseFile(self::SCHEMA_DIR . '/MapiFieldsOther.yaml');

        foreach (self::$tagsOther as $propSet => $props) {
            $guid = (string)PropertySetConstants::$propSet();
            if ($guid) {
                self::$tagsOther[$guid] = $props;
                unset(self::$tagsOther[$propSet]);
            }
        }
    }
    
    protected function map()
    {
        //print_r($this->raw->keys());

        foreach ($this->raw->keys() as $key) {
            //echo sprintf('Mapping %s %s'."\n", $key->getGuid(), $key->getCode());

            if ((string)$key->getGuid() == (string)PropertySetConstants::PS_MAPI()) {
                // read from tagsMsg
                //echo '  Seeking '.sprintf('%04x', $key->getCode())."\n";
                $propertyName  = strtolower($key->getCode());
                $schemaElement = self::$tagsMsg[sprintf('%04x', $key->getCode())] ?? null;
                if ($schemaElement) {                    
                    $propertyName = strtolower(preg_replace('/^[^_]*_/', '', $schemaElement[0]));
                    //echo '    Found msg '.$propertyName."\n";
                }
                $this->map[$propertyName] = $key;
            }
            else {
                // read from tagsOther
                $propertyName = strtolower($key->getCode());
                $schemaElement = self::$tagsOther[(string)$key->getGuid()][$key->getCode()] ?? null;
                if ($schemaElement) {
                    $propertyName = $schemaElement;                    
                    //echo '    Found other '.$propertyName."\n";
                }
                $this->map[$propertyName] = $key;
            }
        }

    }


    protected function resolveName($name)
    {
        if (isset($this->map[$name])) {
            return $this->map[$name];
        }
        return new PropertyKey($name);
    }

    protected function resolveKey($code, $guid = null)
    {
        if (is_string($code) && is_null($guid)) {
            return $this->resolveName($code);
        }
        return new PropertyKey($code, $guid);
    }

    /* public methods */

    public function getStore()
    {
        return $this->store;
    }

    public function get($code, $guid = null)
    {
        $val = $this->raw->get($this->resolveKey($code, $guid));
        
        // resolve streams when they're requested
        if (is_callable($val)) {
            
            $val = $val();
           
        }

        return $val;
    }

    public function set($code, $value, $guid = null)
    {        
        $this->raw->set($this->resolveKey($code, $guid), $value);
    }

    public function delete($code, $guid = null)
    {
        $this->raw->delete($this->resolveKey($code, $guid));
    }

    /* magic methods */

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    public function offsetExists($offset): bool
    {
        return (!is_null($this->raw->get($this->resolveKey($offset))));
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->delete($offset);
    }
}