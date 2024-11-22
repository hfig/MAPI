<?php

namespace Hfig\MAPI\OLE\RTF;

// this is a partial implementation of the Ruby stringscanner class
// it seemed like a moderately useful concept, even though the
// parser logic in the ruby-msg library (and ported) is pretty awful

class StringScanner implements \Stringable
{
    private $pos;
    private $last;

    public function __construct(private $buffer)
    {
        $this->pos = 0;
    }

    public function scan($str)
    {
        $len = strlen((string) $str);
        if (substr((string) $this->buffer, $this->pos, $len) == $str) {
            $this->pos += $len;
            $this->last = $str;

            return $this->last;
        }

        return false;
    }

    public function scanRegex($regex)
    {
        if (preg_match($regex, (string) $this->buffer, $matches, PREG_OFFSET_CAPTURE, $this->pos)) {
            if ($matches[0][1] == $this->pos) {
                $this->pos += strlen($matches[0][0]);
                $this->last = $matches;

                return $this->last;
            }
        }

        return false;
    }

    public function scanUntil($str)
    {
        if (($newpos = strpos((string) $this->buffer, (string) $str, $this->pos)) !== false) {
            $this->last = substr((string) $this->buffer, $this->pos, $newpos - $this->pos);
            $this->pos  = $newpos + strlen((string) $str);

            return $this->last;
        }

        return false;
    }

    public function scanUntilRegex($regex)
    {
        if (preg_match($regex, (string) $this->buffer, $matches, PREG_OFFSET_CAPTURE, $this->pos)) {
            $mlen       = strlen($matches[0][0]);
            $this->last = substr((string) $this->buffer, $this->pos, $matches[0][1] + $mlen);
            $this->pos  = $matches[0][1] + $mlen;

            return $this->last;
        }

        return false;
    }

    public function eos(): bool
    {
        return $this->pos >= strlen((string) $this->buffer);
    }

    public function increment($count = 1)
    {
        $this->last = substr((string) $this->buffer, $this->pos, $count);
        $this->pos += $count;

        return $this->last;
    }

    public function result()
    {
        return $this->last;
    }

    public function __toString(): string
    {
        return substr((string) $this->buffer, $this->pos);
    }
}
