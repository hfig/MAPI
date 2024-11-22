<?php

namespace Hfig\MAPI\Item;

abstract class Attachment extends MapiObject
{
    protected $embedded_msg;
    protected $embedded_ole;

    public function getFilename()
    {
        return $this->properties['attach_long_filename'] ?? $this->properties['attach_filename'] ?? '';
    }

    public function getData()
    {
        return $this->embedded_msg ?? $this->embedded_ole ?? $this->properties['attach_data'] ?? null;
    }

    public function copyToStream($stream): void
    {
        if ($this->embedded_ole) {
            $this->storeEmbeddedOle($stream);
        }
        fwrite($stream, $this->getData() ?? '');
    }

    protected function storeEmbeddedOle($stream): void
    {
        // this is very untested...
        // throw new \RuntimeException('Saving an OLE Compound Document is not supported');

        $this->embedded_ole->saveToStream($stream);
    }
}
