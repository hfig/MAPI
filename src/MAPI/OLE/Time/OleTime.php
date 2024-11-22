<?php

namespace Hfig\MAPI\OLE\Time;

use OLE;

class OleTime
{
    /**
     * Convert OLE-bytestring to unix timestamp in seconds.
     *
     * Input is little-endian encoded number which equal amount of 100-nanoseconds
     *   since 1 January 1601 (FILETIME-structure)
     * Not any longer adapted from PEAR::OLE (which we assumed is correct)
     *
     * @see https://docs.microsoft.com/en-us/openspecs/windows_protocols/ms-oleps/bf7aeae8-c47a-4939-9f45-700158dac3bc
     *
     * @return int
     */
    public static function getTimeFromOleTime($string): int|float
    {
        if (strlen((string) $string) !== 8) {
            return 0;
        }

        // date is encoded as little endian integer
        $big_date = unpack('P', (string) $string)[1];

        // translate to seconds
        $big_date /= 10000000;

        // days from 1-1-1601 until the beginning of UNIX era
        $days = 134774;

        // translate to seconds from beginning of UNIX era
        $big_date -= ($days * 24 * 3600);

        return floor($big_date);
    }
}
