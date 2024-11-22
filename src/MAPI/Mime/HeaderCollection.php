<?php

namespace Hfig\MAPI\Mime;

class HeaderCollection implements \IteratorAggregate
{
    protected $rawHeaders = [];

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->rawHeaders);
    }

    public function add($header, $value = null): void
    {
        if (is_null($value)) {
            // echo $header . "\n";
            @[$header, $value] = explode(':', (string) $header, 2);
            // if (!$value) throw new \Exception('No value for ' . $header);
            $value = ltrim($value);
        }

        $key = strtolower((string) $header);
        $val = [
            'rawkey' => $header,
            'value'  => $value,
        ];
        $val = (object) $val;

        if (isset($this->rawHeaders[$key])) {
            if (!is_array($this->rawHeaders[$key])) {
                $this->rawHeaders[$key] = [$this->rawHeaders[$key]];
            }

            $this->rawHeaders[$key][] = $val;
        } else {
            $this->rawHeaders[$key] = $val;
        }
    }

    public function set($header, $value): void
    {
        $key = strtolower((string) $header);
        $val = [
            'rawkey' => $header,
            'value'  => $value,
        ];
        $val = (object) $val;

        $this->rawHeaders[$key] = $val;
    }

    public function get($header)
    {
        $key = strtolower((string) $header);
        if (!isset($this->rawHeaders[$key])) {
            return null;
        }

        return $this->rawHeaders[$key];
    }

    public function getValue($header)
    {
        $raw = $this->get($header);

        if (is_null($raw)) {
            return null;
        }
        if (is_array($raw)) {
            return array_map(fn ($e) => $e->value, $raw);
        }

        return $raw->value;
    }

    public function has($header): bool
    {
        $key = strtolower((string) $header);

        return isset($this->rawHeaders[$key]);
    }

    public function unset($header): void
    {
        $key = strtolower((string) $header);
        unset($this->rawHeaders[$key]);
    }
}
