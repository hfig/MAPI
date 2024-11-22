<?php

namespace Hfig\MAPI\Property;

class PropertyKey
{
    private readonly string $code;
    private readonly string $guid;

    public function __construct(
        int|string $code,
        ?string $guid = null,
    ) {
        $this->code = (string) $code;
        $this->guid = $guid ?: (string) PropertySetConstants::PS_MAPI();
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

    public static function getHashOf(string $code, ?string $guid = null): string
    {
        if (empty($guid)) {
            $guid = (string) PropertySetConstants::PS_MAPI();
        }

        return $code.'::'.$guid;
    }
}
