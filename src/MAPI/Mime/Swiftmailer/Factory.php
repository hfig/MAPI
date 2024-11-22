<?php

namespace Hfig\MAPI\Mime\Swiftmailer;

use Hfig\MAPI\Mime\ConversionFactory;
use Hfig\MAPI\OLE\CompoundDocumentElement as Element;

class Factory implements ConversionFactory
{
    protected bool $muteConversionExceptions;

    public function __construct(bool $muteConversionExceptions = false)
    {
        $this->muteConversionExceptions = $muteConversionExceptions;
    }

    public function parseMessage(Element $root): Message
    {
        $message = new Message($root);
        $message->setMuteConversionExceptions($this->muteConversionExceptions);

        return $message;
    }
}
