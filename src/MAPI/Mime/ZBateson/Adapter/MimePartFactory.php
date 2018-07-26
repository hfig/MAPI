<?php

namespace Hfig\MAPI\Mime\ZBatesonAdapter;

use ZBateson\MailMimeParser\Message\MimePartFactory as BaseMimePartFactory;

class MimePartFactory extends BaseMimePartFactory
{
    public function newMimePart()
    {
        return new MimePart($this->headerFactory, $this->messageWriterService->getMimePartWriter());
    }
}