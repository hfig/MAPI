<?php

namespace Hfig\MAPI\Mime\Swiftmailer;

use Hfig\MAPI\Mime\ConversionFactory;
use Hfig\MAPI\OLE\CompoundDocumentElement as Element;


class Factory implements ConversionFactory
{
    public function parseMessage(Element $root)
    {        
        return new \Hfig\MAPI\Mime\Swiftmailer\Message($root);
    }
}