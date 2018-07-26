<?php

namespace Hfig\MAPI\Item;

abstract class Attachment extends Object
{
    protected $embedded_msg = null;
    protected $embeded_ole = null;

    public function getFilename()
    {
        return $this->properties['attach_long_filename'] ?? $this->properties['attach_filename'] ?? '';
    }

    public function getData()
    {
        return $this->embedded_msg ?? $this->embeded_ole ?? $this->properties['attach_data'] ?? null;
    }

    public function copyToStream($stream)
    {
        if ($this->embedded_ole) {
            return $this->storeEmbeddedOle($stream);
        }
        fwrite($stream, $this->getData() ?? '');
    }

    protected function storeEmbeddedOle($stream)
    {
        // this is very untested...
        //throw new \RuntimeException('Saving an OLE Compound Document is not supported');

        $this->embeded_ole->saveToStream($stream);
    }


}