<?php

declare(strict_types=1);

namespace Hfig\MAPI\Tests\Property;

use Hfig\MAPI\Property\PropertySetConstants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface as OleGuidInterface;

class PropertySetConstantsTest extends TestCase
{
    #[DataProvider('provide_method_cases')]
    public function testMethodExists(string $method): void
    {
        $guid = PropertySetConstants::{$method}();

        $this->assertInstanceOf(OleGuidInterface::class, $guid);
    }

    #[DataProvider('provide_method_cases')]
    public function testMethodReturnsSameObjectWhenCalledTwice(string $method): void
    {
        $this->assertSame(
            PropertySetConstants::{$method}(),
            PropertySetConstants::{$method}()
        );
    }

    public static function provide_method_cases(): iterable
    {
        foreach (PropertySetConstants::NAMES as $name) {
            yield $name => [$name];
        }
    }
}
