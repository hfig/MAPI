<?php

namespace Hfig\MAPI\Property;

use Hfig\MAPI\OLE\CompoundDocumentElement as Element;

class PropertyStoreEncodings
{
    public const ENCODERS = [
        0x000d => 'decode0x000d',
        0x001f => 'decode0x001f',
        0x001e => 'decode0x001e',
        0x0203 => 'decode0x0102',
    ];

    public static function decode0x000d(Element $e):Element
    {
        return $e;
    }

    public static function decode0x001f(Element $e)
    {
        return mb_convert_encoding( $e->getData(), 'UTF-8', 'UTF-16LE');
    }

    public static function decode0x001e(Element $e)
    {
        return trim($e->getData());
    }

    public static function decode0x0102(Element $e)
    {
        return $e->getData();
    }

    public static function decodeUnknown(Element $e)
    {
        return $e->getData();
    }

    public static function decode($encoding, Element $e)
    {
        if (isset(self::ENCODERS[$encoding])) {
            $fn = self::ENCODERS[$encoding];
            return self::$fn($e);
        }
        return self::decodeUnknown($e);

    }

    public static function getDecoder($encoding)
    {
        if (isset(self::ENCODERS[$encoding])) {
            $fn = self::ENCODERS[$encoding];
            return self::$fn;
        }
        return self::decodeUnknown;
    }

    public static function decodeFunction($encoding, Element $e)
    {
        return function() use ($encoding, $e) {
            return PropertyStoreEncodings::decode($encoding, $e);
        };
    }
}