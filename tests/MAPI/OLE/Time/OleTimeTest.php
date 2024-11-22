<?php

namespace Hfig\MAPI\Tests\OLE\Time;

use Hfig\MAPI\OLE\Time\OleTime;
use PHPUnit\Framework\TestCase;

class OleTimeTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('getTimeFromOleTimeProvider')]
    public function testGetTimeFromOleTime(int $number, string $input, int $expected): void
    {
        $actual = OleTime::getTimeFromOleTime($input);

        $this->assertEquals($expected, $actual, sprintf('Failed test %d', $number));
    }

    public static function getTimeFromOleTimeProvider(): array
    {
        return [
            [1, hex2bin('4012a294ea41c601'), 1141737919],
        ];
    }
}
