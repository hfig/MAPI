<?php

namespace Hfig\MAPI\Property;

class PropertyCollection implements \IteratorAggregate
{
    private array $col = [];

    public function set(PropertyKey $key, $value): void
    {
        // echo sprintf('Setting for %s %s'."\n", $key->getCode(), $key->getGuid());
        $this->col[$key->getHash()] = ['key' => $key, 'value' => $value];
    }

    public function delete(PropertyKey $key): void
    {
        unset($this->col[$key->getHash()]);
    }

    public function get(PropertyKey $key)
    {
        $bucket = $this->col[$key->getHash()] ?? null;
        if (is_null($bucket)) {
            return null;
        }

        return $bucket['value'];
    }

    public function has(PropertyKey $key): bool
    {
        return isset($this->col[$key->getHash()]);
    }

    public function keys(): array
    {
        return array_map(fn ($bucket) => $bucket['key'], $this->col);
    }

    public function values(): array
    {
        return array_map(fn ($bucket) => $bucket['value'], $this->col);
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->col as $bucket) {
            yield $bucket['key'] => $bucket['value'];
        }
    }
}
