<?php

namespace Hfig\MAPI\Mime\ZBatesonAdapter;

use ZBateson\MailMimeParser\Header\AbstractHeader;

class MultiGenericHeader extends AbstractHeader {

    protected $elements;

    public function __construct(ConsumerService $consumerService, $name, $value)
    {

    }


    protected function getConsumer(ConsumerService $consumerService)
    {
        static $consumer;
        if (!$consumer) $consumer = new MultiGenericConsumer();
        
        return $consumer;
    }

}