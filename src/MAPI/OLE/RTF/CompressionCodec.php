<?php

namespace Hfig\MAPI\OLE\RTF;

class CompressionCodec
{
    public const DICT = '{\\rtf1\\ansi\\mac\\deff0\\deftab720{\\fonttbl;}'.
        '{\\f0\\fnil \\froman \\fswiss \\fmodern \\fscript '.
        '\\fdecor MS Sans SerifSymbolArialTimes New RomanCourier'.
        "{\\colortbl\\red0\\green0\\blue0\n\r\\par ".
        '\\pard\\plain\\f0\\fs20\\b\\i\\u\\tab\\tx';
    public const BLOCKSIZE  = 4096;
    public const HEADERSIZE = 16;

    // this is adapted from Java libpst instead of Ruby ruby-msg
    private static function uncompress($raw, $compressedSize, $uncompressedSize): string
    {
        $buf = str_pad(self::DICT, self::BLOCKSIZE, "\0");
        $wp  = strlen(self::DICT);

        $pos   = self::HEADERSIZE;
        $data  = '';
        $eof   = strlen((string) $raw);
        $flags = 0;

        while ($pos < $eof && strlen($data) < $uncompressedSize) {
            $flags = ord($raw[$pos++]) & 0xFF;
            for ($x = 0; $x < 8; ++$x) {
                $isRef = (($flags & 1) == 1);
                $flags >>= 1;

                if ($isRef) {
                    // get the starting point for the buffer and the
                    // length to read
                    $refOffsetOrig = ord($raw[$pos++]) & 0xFF;
                    $refSizeOrig   = ord($raw[$pos++]) & 0xFF;
                    $refOffset     = ($refOffsetOrig << 4) | ($refSizeOrig >> 4);
                    $refSize       = ($refSizeOrig & 0xF) + 2;
                    // $refOffset &= 0xFFF;

                    // copy the data from the buffer
                    $index = $refOffset;
                    for ($y = 0; $y < $refSize; ++$y) {
                        $data .= $buf[$index];

                        if (strlen($data) >= $uncompressedSize) {
                            break;
                        }

                        $buf[$wp] = $buf[$index];

                        $wp    = ($wp + 1)    % self::BLOCKSIZE;
                        $index = ($index + 1) % self::BLOCKSIZE;
                    }
                } else {
                    $buf[$wp] = $raw[$pos];
                    $wp       = ($wp + 1) % self::BLOCKSIZE;

                    $data .= $raw[$pos++];
                }

                if (strlen($data) >= $uncompressedSize) {
                    break;
                }
                if ($pos >= $eof) {
                    break;
                }
            }
        }

        // echo 'Decompressed: ', $data, "\n"; die();
        return $data;
    }

    public static function decode($data): string
    {
        $result = '';
        // echo 'Data: ' . bin2hex($data), "\n";
        // echo 'Len: ' . strlen($data), "\n";

        $header                                              = array_values(unpack('Vcs/Vus/a4m/Vcrc', (string) $data));
        [$compressedSize, $uncompressedSize, $magic, $crc32] = $header;

        if ($magic == 'MELA') {
            $data = substr((string) $data, self::HEADERSIZE, $uncompressedSize);
        } elseif ($magic == 'LZFu') {
            $data = self::uncompress($data, $compressedSize, $uncompressedSize);
        } else {
            throw new \Exception('Unknown stream data type '.$magic);
        }

        return rtrim($data, "\0");
    }

    /**
     * @comment see Kopano-core Mapi4Linux or Python delimitry/compressed_rtf
     */
    public static function encode($data): string
    {
        $uncompressedSize = strlen((string) $data);
        $compressedSize   = $uncompressedSize + self::HEADERSIZE;

        return pack('V/V/a4/V/a*', $compressedSize, $uncompressedSize, 'MELA', $data);
    }
}
