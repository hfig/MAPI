<?php

namespace Hfig\MAPI\Mime\ZBatesonAdapter;

use ZBateson\MailMimeParser\Message\MimePart as BaseMimePart;

class MimePart extends BaseMimePart
{
    public function setRawHeader($name, $value)
    {
        $key = strtolower($name);


        $newheader = $this->headerFactory->newInstance($name, $value);
        if ($newheader instanceof \ZBateson\MailMimeParser\Header\GenericHeader) {
            // generic headers can be duplicate
        }

        if (isset($this->headers[$key])) {
            
        }



        $this->headers[strtolower($name)] = $this->headerFactory->newInstance($name, $value);
    }
}