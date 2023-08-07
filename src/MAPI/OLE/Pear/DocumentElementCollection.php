<?php

namespace Hfig\MAPI\OLE\Pear;

use OLE;

class DocumentElementCollection implements \ArrayAccess, \IteratorAggregate
{
    /** @var OLE */
    private $ole;
    private $col = [];
    private $proxy_col = [];

    public function __construct(OLE $ole, array &$collection = null)
    {
        if (is_null($collection)) {
            $tmpcol = [];
            $collection =& $tmpcol;
        }
        $this->col = &$collection;
        $this->ole = $ole;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->col as $k => $v)
        {
            yield $k => $this->offsetGet($k);
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->col[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        if (!isset($this->col[$offset])) {
            return null;
        }

        if (!isset($this->proxy_col[$offset])) {
            $this->proxy_col[$offset] = new DocumentElement($this->ole, $this->col[$offset]);
        }

        return $this->proxy_col[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof DocumentElement) {
            throw new \InvalidArgumentException('Collection must contain DocumentElement instances');
        }

        $this->proxy_col[$offset] = $value;
        $this->col[$offset] = $value->unwrap();
    }

    public function offsetUnset($offset): void
    {
        unset($this->proxy_col[$offset]);
        unset($this->col[$offset]);
    }
}