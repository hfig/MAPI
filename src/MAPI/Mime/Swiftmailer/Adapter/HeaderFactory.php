<?php

namespace Hfig\MAPI\Mime\Swiftmailer\Adapter;

use \Swift_Mime_SimpleHeaderFactory;
use \Swift_Mime_HeaderEncoder;
use \Swift_Mime_Header;
use \Swift_Encoder;
use \Swift_AddressEncoder;
use Egulias\EmailValidator\EmailValidator;

class HeaderFactory extends Swift_Mime_SimpleHeaderFactory
{
    protected $encoder;
    protected $charset;

    public function __construct(Swift_Mime_HeaderEncoder $encoder, Swift_Encoder $paramEncoder, EmailValidator $emailValidator, $charset = null, Swift_AddressEncoder $addressEncoder = null)
    {
        parent::__construct($encoder, $paramEncoder, $emailValidator, $charset, $addressEncoder);

        $this->encoder = $encoder;
        $this->charset = $charset;
    }

    public function createTextHeader($name, $value = null): UnstructuredHeader
    {
        $header = new UnstructuredHeader($name, $this->encoder);
        if (isset($value)) {
            $header->setFieldBodyModel($value);
        }
        $this->setHeaderCharset($header);

        return $header;
    }

    protected function setHeaderCharset(Swift_Mime_Header $header): void
    {
        if (isset($this->charset)) {
            $header->setCharset($this->charset);
        }
    }
}