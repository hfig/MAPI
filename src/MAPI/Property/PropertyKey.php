<?php

namespace Hfig\MAPI\Property;

class PropertyKey {

    private $code;
    private $guid;

    public function __construct($code, $guid = null)
    {
        
        if (!$guid) {
            $guid = PropertySetConstants::PS_MAPI();
        }

        $guid = (string)$guid;

        $this->code = $code;
        $this->guid = $guid;

        //echo '  Created with code ' . $code . "\n";
    }

    public function getHash(): string
    {
        return static::getHashOf($this->code, $this->guid);
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getGuid()
    {
        return $this->guid;
    }

    public static function getHashOf($code, $guid = null): string
    {
        if (!$guid) {
            $guid = PropertySetConstants::PS_MAPI();
        }
        $guid = (string)$guid;

        return $code . '::' . $guid;
    }

    
}