<?php

namespace Hfig\MAPI\Mime\Swiftmailer;

use Hfig\MAPI\Message\Message as BaseMessage;
use Hfig\MAPI\Mime\HeaderCollection;
use Hfig\MAPI\Mime\MimeConvertible;
use Hfig\MAPI\Mime\Swiftmailer\Adapter\DependencySet;


// maybe should use decorator pattern? lots to reimplement then though

class Message extends BaseMessage implements MimeConvertible
{
    protected $conversionExceptionsList = [];
    protected $muteConversionExceptions = false;

    public static function wrap(BaseMessage $message)
    {
        if ($message instanceof MimeConvertible) {
            return $message;
        }

        return new self($message->obj, $message->parent);
    }

    public function toMime()
    {
        DependencySet::register();

        $message = new \Swift_Message();
        $message->setEncoder(new \Swift_Mime_ContentEncoder_RawContentEncoder());


        // get headers
        $headers = $this->translatePropertyHeaders();

        // add them to the message
        $add = [$message, 'setTo']; // function
        try {
            $this->addRecipientHeaders('To', $headers, $add);
        }
        catch (\Swift_RfcComplianceException $e) {
            if (!$this->muteConversionExceptions) {
                throw $e;
            }
            $this->conversionExceptionsList[] = $e;
        }
        $headers->unset('To');

        $add = [$message, 'setCc']; // function
        try {
            $this->addRecipientHeaders('Cc', $headers, $add);
        }
        catch (\Swift_RfcComplianceException $e) {
            if (!$this->muteConversionExceptions) {
                throw $e;
            }
            $this->conversionExceptionsList[] = $e;
        }
        $headers->unset('Cc');

        $add = [$message, 'setBcc']; // function
        try {
            $this->addRecipientHeaders('Bcc', $headers, $add);
        }
        catch (\Swift_RfcComplianceException $e) {
            if (!$this->muteConversionExceptions) {
                throw $e;
            }
            $this->conversionExceptionsList[] = $e;}
        $headers->unset('Bcc');

        $add = [$message, 'setFrom']; // function
        try {
            $this->addRecipientHeaders('From', $headers, $add);
        }
        catch (\Swift_RfcComplianceException $e) {
            if (!$this->muteConversionExceptions) {
                throw $e;
            }
            $this->conversionExceptionsList[] = $e;
        }
        $headers->unset('From');


        try {
            $message->setId(trim($headers->getValue('Message-ID'), '<>'));
        }
        catch (\Swift_RfcComplianceException $e) {
            if (!$this->muteConversionExceptions) {
                throw $e;
            }
            $this->conversionExceptionsList[] = $e;
        }

        try {
            $message->setDate(new \DateTime($headers->getValue('Date')));
        }
        catch (\Exception $e) { // the \DateTime can throw \Exception
            if (!$this->muteConversionExceptions) {
                throw $e;
            }
            $this->conversionExceptionsList[] = $e;
        }

        if ($boundary = $this->getMimeBoundary($headers)) {
            $message->setBoundary($boundary);
        }


        $headers->unset('Message-ID');
        $headers->unset('Date');
        $headers->unset('Mime-Version');
        $headers->unset('Content-Type');

        $add = [$message->getHeaders(), 'addTextHeader'];
        $this->addPlainHeaders($headers, $add);


        // body
        $hasHtml = false;
        $bodyBoundary = '';
        if ($boundary) {
            if (preg_match('~^_(\d\d\d)_([^_]+)_~', $boundary, $matches)) {
                $bodyBoundary = sprintf('_%03d_%s_', (int)$matches[1]+1, $matches[2]);
            }
        }
        try {
            $html = $this->getBodyHTML();
            if ($html) {
                $hasHtml = true;
            }
        }
        catch (\Exception $e) { // getBodyHTML() can throw \Exception
            if (!$this->muteConversionExceptions) {
                throw $e;
            }
            $this->conversionExceptionsList[] = $e;
        }

        if (!$hasHtml) {
            try {
                $message->setBody($this->getBody(), 'text/plain');
            }
            catch (\Exception $e) { // getBody() can throw \Exception
                if (!$this->muteConversionExceptions) {
                    throw $e;
                }
                $this->conversionExceptionsList[] = $e;
            }
        }
        else {
            // build multi-part
            // (simple method is to just call addPart() on message but we can't control the ID
            $multipart = new \Swift_Attachment();
            $multipart->setContentType('multipart/alternative');
            $multipart->setEncoder($message->getEncoder());
            if ($bodyBoundary) {
                $multipart->setBoundary($bodyBoundary);
            }
            try {
                $multipart->setBody($this->getBody(), 'text/plain');
            }
            catch (\Exception $e) { // getBody() can throw \Exception
                if (!$this->muteConversionExceptions) {
                    throw $e;
                }
                $this->conversionExceptionsList[] = $e;
            }

            $part = new \Swift_MimePart($html, 'text/html', null);
            $part->setEncoder($message->getEncoder());


            $message->attach($multipart);
            $multipart->setChildren(array_merge($multipart->getChildren(), [$part]));
        }


        // attachments
        foreach ($this->getAttachments() as $a) {
            $wa = Attachment::wrap($a);
            $attachment = $wa->toMime();

            $message->attach($attachment);
        }

        return $message;
    }

    public function toMimeString(): string
    {
        return (string) $this->toMime();
    }

    public function copyMimeToStream($stream)
    {
        // TODO: use \Swift_Message::toByteStream instead
        fwrite($stream, $this->toMimeString());
    }

    public function setMuteConversionExceptions(bool $muteConversionExceptions)
    {
        $this->muteConversionExceptions = $muteConversionExceptions;
    }

    protected function addRecipientHeaders($field, HeaderCollection $headers, callable $add)
    {
        $recipient = $headers->getValue($field);

        if (is_null($recipient)) {
            return;
        }

        if (!is_array($recipient)) {
            $recipient = [$recipient];
        }


        $map = [];
        foreach ($recipient as $r) {
            if (preg_match('/^((?:"[^"]*")|.+) (<.+>)$/', $r, $matches)) {
                $map[trim($matches[2], '<>')] = $matches[1];
            }
            else {
                $map[] = $r;
            }
        }

        $add($map);
    }

    protected function addPlainHeaders(HeaderCollection $headers, callable $add)
    {
        foreach ($headers as $key => $value)
        {
            if (is_array($value)) {
                foreach ($value as $ikey => $ivalue) {
                    $header = $ivalue->rawkey;
                    $value  = $ivalue->value;
                    $add($header, $value);
                }
            }
            else {
                $header = $value->rawkey;
                $value  = $value->value;
                $add($header, $value);
            }
        }
    }

    protected function translatePropertyHeaders()
    {
        $rawHeaders = new HeaderCollection();

        // additional headers - they can be multiple lines
        $transport = [];
        $transportKey = 0;

        $transportRaw = explode("\r\n", $this->properties['transport_message_headers']);
        foreach ($transportRaw as $v) {
            if (!$v) continue;

            if ($v[0] !== "\t" && $v[0] !== ' ') {
                $transportKey++;
                $transport[$transportKey] = $v;
            }
            else {
                $transport[$transportKey] = $transport[$transportKey] . "\r\n" . $v;
            }
        }

        foreach ($transport as $header) {
            $rawHeaders->add($header);
        }



        // sender
        $senderType = $this->properties['sender_addrtype'];
        if ($senderType == 'SMTP') {
            $rawHeaders->set('From', $this->getSender());
        }
        elseif (!$rawHeaders->has('From')) {
            if ($from = $this->getSender()) {
                $rawHeaders->set('From', $from);
            }
        }


        // recipients
        foreach ($this->getRecipients() as $r) {
            $rawHeaders->add($r->getType(), (string)$r);
        }

        // subject - preference to msg properties
        if ($this->properties['subject']) {
            $rawHeaders->set('Subject', $this->properties['subject']);
        }

        // date - preference to transport headers
        if (!$rawHeaders->has('Date')) {
            $date = $this->properties['message_delivery_time'] ?? $this->properties['client_submit_time']
                ?? $this->properties['last_modification_time'] ?? $this->properties['creation_time'] ?? null;
            if (!is_null($date)) {
                // ruby-msg suggests this is stored as an iso8601 timestamp in the message properties, not a Windows timestamp
                $date = date('r', strtotime($date));
                $rawHeaders->set('Date', $date);
            }
        }

        // other headers map
        $map = [
            ['internet_message_id', 'Message-ID'],
            ['in_reply_to_id',      'In-Reply-To'],

            ['importance',          'Importance',  function($val) { return ($val == '1') ? null : $val; }],
            ['priority',            'Priority',    function($val) { return ($val == '1') ? null : $val; }],
            ['sensitivity',         'Sensitivity', function($val) { return ($val == '0') ? null : $val; }],

            ['conversation_topic',  'Thread-Topic'],

            //# not sure of the distinction here
            //# :originator_delivery_report_requested ??
            ['read_receipt_requested', 'Disposition-Notification-To', function($val) use ($rawHeaders) {
                $from = $rawHeaders->getValue('From');

                if (preg_match('/^((?:"[^"]*")|.+) (<.+>)$/', $from, $matches)) {
                    $from = trim($matches[2], '<>');
                }
                return $from;
            }]
        ];
        foreach ($map as $do) {
            $value = $this->properties[$do[0]];
            if (isset($do[2])) {
                $value = $do[2]($value);
            }
            if (!is_null($value)) {
                $rawHeaders->set($do[1], $value);
            }
        }

        return $rawHeaders;

    }

    protected function getMimeBoundary(HeaderCollection $headers)
    {
        // firstly - use the value in the headers
        if ($type = $headers->getValue('Content-Type')) {
            if (preg_match('~boundary="([a-zA-z0-9\'()+_,-.\/:=? ]+)"~', $type, $matches)) {
                return $matches[1];
            }
        }

        // if never sent via SMTP then it has to be synthesised
        // this is done using the message id
        if ($mid = $headers->getValue('Message-ID')) {
            $recount = 0;
            $mid = preg_replace('~[^a-zA-z0-9\'()+_,-.\/:=? ]~', '', $mid, -1, $recount);
            $mid = substr($mid, 0, 55);
            return sprintf('_%03d_%s_', $recount, $mid);
        }
        return '';
    }

    /**
     * Returns the list of conversion exceptions.
     *
     * @return array
     */
    public function getConversionExceptionsList() : array {
        return $this->conversionExceptionsList;
    }
}
