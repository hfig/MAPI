<?php

namespace Hfig\MAPI\OLE\RTF;

// this is a partial implementation of the Ruby stringscanner class
// it seemed like a moderately useful concept, even though the
// parser logic in the ruby-msg library (and ported) is pretty awful


class StringScanner
{
    private $buffer;
    private $pos;
    private $last;

    public function __construct($data)
    {
        $this->buffer = $data;
        $this->pos = 0;
    }

    public function scan($str)
    {
        $len = strlen($str);
        if (substr($this->buffer, $this->pos, $len) == $str) {
            $this->pos += $len;
            $this->last = $str;
            return $this->last;
        }
        return false;
    }

    public function scanRegex($regex)
    {
        // The A modifier anchors the match at the offset. Without it, preg_match()
        // searches the whole rest of the buffer before the position check below
        // rejects a later match, which makes scanning quadratic in the input size.
        if (preg_match($regex . 'A', $this->buffer, $matches, PREG_OFFSET_CAPTURE, $this->pos)) {
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
        if (($newpos = strpos($this->buffer, $str, $this->pos)) !== false) {
            $this->last = substr($this->buffer, $this->pos, $newpos - $this->pos);
            $this->pos = $newpos + strlen($str);
            return $this->last;
        }
        return false;
    }

    public function scanUntilRegex($regex)
    {
        if (preg_match($regex, $this->buffer, $matches, PREG_OFFSET_CAPTURE, $this->pos)) {
            $mlen  = strlen($matches[0][0]);
            $this->last = substr($this->buffer, $this->pos, $matches[0][1] + $mlen);
            $this->pos = $matches[0][1] + $mlen;
            return $this->last;
        }
        return false;
    }

    public function eos(): bool
    {
        return $this->pos >= strlen($this->buffer);
    }

    // consumes the next character and every following one that is not in $mask
    public function incrementUntilAny($mask)
    {
        $count = 1 + strcspn($this->buffer, $mask, $this->pos + 1);
        return $this->increment($count);
    }

    public function increment($count = 1)
    {
        $this->last = substr($this->buffer, $this->pos, $count);
        $this->pos += $count;
        return $this->last;
    }

    public function result()
    {
        return $this->last;
    }


    public function __toString()
    {
        return substr($this->buffer, $this->pos);
    }
}
        
