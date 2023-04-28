<?php

namespace Hfig\MAPI\Mime\Swiftmailer;

use Hfig\MAPI\Mime\ConversionFactory;
use Hfig\MAPI\OLE\CompoundDocumentElement as Element;


class Factory implements ConversionFactory
{

    protected $muteConversionExceptions;

    public function __construct(bool $muteConversionExceptions = false)
    {
        $this->muteConversionExceptions = $muteConversionExceptions;
    }

    public function parseMessage(Element $root)
    {
        $message = new \Hfig\MAPI\Mime\Swiftmailer\Message($root);
        $message->setMuteConversionExceptions($this->muteConversionExceptions);

        return $message;
    }
}
