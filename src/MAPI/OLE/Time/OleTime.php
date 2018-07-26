<?php

namespace Hfig\MAPI\OLE\Time;

use OLE;

class OleTime
{
    public static function getTimeFromOleTime($string)
    {
        //return OLE::OLE2LocalDate($string);

        if (strlen($string) != 8) {
            return 0;
        }

        // number of nanoseconds since 1 January 1601
        // adapted from PEAR::OLE (which we assume is correct)

        // factor used for separating numbers into 4 bytes parts
        $factor = pow(2,32);
        $high_part = 0;
        for ($i = 0; $i < 4; $i++) {
            list(, $high_part) = unpack('C', $string[(7 - $i)]);
            if ($i < 3) {
                $high_part *= 0x100;
            }
        }
        $low_part = 0;
        for ($i = 4; $i < 8; $i++) {
            list(, $low_part) = unpack('C', $string[(7 - $i)]);
            if ($i < 7) {
                $low_part *= 0x100;
            }
        }
        $big_date = ($high_part * $factor) + $low_part;
        // translate to seconds
        $big_date /= 10000000;
        
        // days from 1-1-1601 until the beggining of UNIX era
        $days = 134774;
        
        // translate to seconds from beggining of UNIX era
        $big_date -= $days * 24 * 3600;
        return floor($big_date);
    }
}