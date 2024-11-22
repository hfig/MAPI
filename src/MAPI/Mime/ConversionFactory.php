<?php

namespace Hfig\MAPI\Mime;

use Hfig\MAPI\OLE\CompoundDocumentElement as Element;

interface ConversionFactory
{
    public function parseMessage(Element $root);
}
