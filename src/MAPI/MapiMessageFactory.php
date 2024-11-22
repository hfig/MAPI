<?php

namespace Hfig\MAPI;

use Hfig\MAPI\Mime\ConversionFactory;
use Hfig\MAPI\OLE\CompoundDocumentElement as Element;

class MapiMessageFactory
{
    private $parent;

    public function __construct(?ConversionFactory $conversionFactory = null)
    {
        $this->parent = $conversionFactory;
    }

    public function parseMessage(Element $root)
    {
        if ($this->parent instanceof ConversionFactory) {
            return $this->parent->parseMessage($root);
        }

        return new Message\Message($root);
    }
}
