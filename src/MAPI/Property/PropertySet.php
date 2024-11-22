<?php

namespace Hfig\MAPI\Property;

use Symfony\Component\Yaml\Yaml;

class PropertySet implements \ArrayAccess
{
    public const SCHEMA_DIR = __DIR__.'/../Schema';

    private PropertyCollection $raw;

    private static $tagsMsg;
    private static $tagsOther;
    private array $map = [];

    public function __construct(private readonly PropertyStore $store)
    {
        $this->raw = $this->store->getCollection();

        if (!self::$tagsMsg || !self::$tagsOther) {
            $this->init();
        }

        $this->map();
    }

    private function init(): void
    {
        self::$tagsMsg   = Yaml::parseFile(self::SCHEMA_DIR.'/MapiFieldsMessage.yaml');
        self::$tagsOther = Yaml::parseFile(self::SCHEMA_DIR.'/MapiFieldsOther.yaml');

        foreach (self::$tagsOther as $propSet => $props) {
            $guid = (string) PropertySetConstants::$propSet();
            if ($guid !== '' && $guid !== '0') {
                self::$tagsOther[$guid] = $props;
                unset(self::$tagsOther[$propSet]);
            }
        }
    }

    protected function map(): void
    {
        // print_r($this->raw->keys());

        foreach ($this->raw->keys() as $key) {
            // echo sprintf('Mapping %s %s'."\n", $key->getGuid(), $key->getCode());

            if ((string) $key->getGuid() === (string) PropertySetConstants::PS_MAPI()) {
                // read from tagsMsg
                // echo '  Seeking '.sprintf('%04x', $key->getCode())."\n";
                $propertyName  = strtolower((string) $key->getCode());
                $schemaElement = self::$tagsMsg[sprintf('%04x', $key->getCode())] ?? null;
                if ($schemaElement) {
                    $propertyName = strtolower(preg_replace('/^[^_]*_/', '', (string) $schemaElement[0]));
                    // echo '    Found msg '.$propertyName."\n";
                }
                $this->map[$propertyName] = $key;
            } else {
                // read from tagsOther
                $propertyName  = strtolower((string) $key->getCode());
                $schemaElement = self::$tagsOther[(string) $key->getGuid()][$key->getCode()] ?? null;
                if ($schemaElement) {
                    $propertyName = $schemaElement;
                    // echo '    Found other '.$propertyName."\n";
                }
                $this->map[$propertyName] = $key;
            }
        }
    }

    protected function resolveName($name)
    {
        return $this->map[$name] ?? new PropertyKey($name);
    }

    protected function resolveKey($code, $guid = null)
    {
        if (is_string($code) && is_null($guid)) {
            return $this->resolveName($code);
        }

        return new PropertyKey($code, $guid);
    }

    /* public methods */

    public function getStore(): PropertyStore
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

    public function set($code, $value, $guid = null): void
    {
        $this->raw->set($this->resolveKey($code, $guid), $value);
    }

    public function delete($code, $guid = null): void
    {
        $this->raw->delete($this->resolveKey($code, $guid));
    }

    /* magic methods */

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value): void
    {
        $this->set($name, $value);
    }

    public function offsetExists($offset): bool
    {
        // return (!is_null($this->get($offset)));
        return !is_null($this->raw->get($this->resolveKey($offset)));
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
