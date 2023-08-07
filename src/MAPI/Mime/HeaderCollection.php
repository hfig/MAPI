<?php

namespace Hfig\MAPI\Mime;

class HeaderCollection implements \IteratorAggregate
{
    protected $rawHeaders = [];

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->rawHeaders);
    }
    
    public function add($header, $value = null)
    {
        if (is_null($value)) {
            //echo $header . "\n";
            @list($header, $value) = explode(':', $header, 2);
            //if (!$value) throw new \Exception('No value for ' . $header);
            $value = ltrim($value);
        }

        $key = strtolower($header);
        $val = [
            'rawkey' => $header,
            'value'  => $value,
        ];
        $val = (object)$val;


        if (isset($this->rawHeaders[$key])) {
            if (!is_array($this->rawHeaders[$key])) {
                $this->rawHeaders[$key] = [ $this->rawHeaders[$key] ];
            }
            
            $this->rawHeaders[$key][] = $val;
        }
        else {
            $this->rawHeaders[$key] = $val;
        }
    }

    public function set($header, $value)
    {
        $key = strtolower($header);
        $val = [
            'rawkey' => $header,
            'value'  => $value,
        ];
        $val = (object)$val;

        $this->rawHeaders[$key] = $val;
    }

    public function get($header)
    {
        $key = strtolower($header);
        if (!isset($this->rawHeaders[$key])) {
            return null;
        }

        return $this->rawHeaders[$key];
    }

    public function getValue($header)
    {
        $raw = $this->get($header);
        
        if (is_null($raw)) return null;
        if (is_array($raw)) {
            return array_map(function ($e) {
                return $e->value;
            }, $raw);
        }

        return $raw->value;

    }

    public function has($header) 
    {
        $key = strtolower($header);
        return isset($this->rawHeaders[$key]);
    }

    public function unset($header) 
    {
        $key = strtolower($header);
        unset($this->rawHeaders[$key]);
    }
}