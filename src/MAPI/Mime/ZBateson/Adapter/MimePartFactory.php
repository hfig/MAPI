<?php

namespace Hfig\MAPI\Mime\ZBateson\Adapter;

use ZBateson\MailMimeParser\Message\MimePartFactory as BaseMimePartFactory;

class MimePartFactory extends BaseMimePartFactory
{
    public function newMimePart()
    {
        return new MimePart($this->headerFactory, $this->messageWriterService->getMimePartWriter());
    }
}