<?php

namespace Hfig\MAPI\OLE\Guid;

use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\Codec\GuidStringCodec;
use Ramsey\Uuid\UuidInterface as OleGuidInterface;

class OleGuid
{
    /** @var UuidFactory */
    private static $factory = null;

    protected static function getFactory(): UuidFactory
    {
        if (!self::$factory) {
            self::$factory = new UuidFactory();
            self::$factory->setCodec(
                new GuidStringCodec(self::$factory->getUuidBuilder())
            );
        }

        return self::$factory;
    }

    public static function fromBytes($bytes): OleGuidInterface
    {
        return self::getFactory()->fromBytes($bytes);
    }

    public static function fromString($guid): OleGuidInterface
    {
        return self::getFactory()->fromString($guid);
    }
}