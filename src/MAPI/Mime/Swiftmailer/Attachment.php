<?php

namespace Hfig\MAPI\Mime\Swiftmailer;

use Hfig\MAPI\Message\Attachment as BaseAttachment;
use Hfig\MAPI\Mime\MimeConvertible;
use Hfig\MAPI\Mime\Swiftmailer\Adapter\DependencySet;

class Attachment extends BaseAttachment implements MimeConvertible
{
    public static function wrap(BaseAttachment $attachment)
    {
        if ($attachment instanceof MimeConvertible) {
            return $attachment;
        }

        return new self($attachment->obj, $attachment->parent);
    }

    public function toMime(): \Swift_Attachment
    {
        DependencySet::register();

        $attachment = new \Swift_Attachment();

        if ($this->getMimeType() != 'Microsoft Office Outlook Message') {
            $attachment->setFilename($this->getFilename());
            $attachment->setContentType($this->getMimeType());
        } else {
            $attachment->setFilename($this->getFilename().'.eml');
            $attachment->setContentType('message/rfc822');
        }

        if ($data = $this->properties['attach_content_disposition']) {
            $attachment->setDisposition($data);
        }

        if ($data = $this->properties['attach_content_location']) {
            $attachment->getHeaders()->addTextHeader('Content-Location', $data);
        }

        if ($data = $this->properties['attach_content_id']) {
            $attachment->setId($data);
        }

        if ($this->embedded_msg) {
            $attachment->setBody(
                Message::wrap($this->embedded_msg)->toMime(),
            );
        } elseif ($this->embedded_ole) {
            // in practice this scenario doesn't seem to occur
            // MS Office documents are attached as files not
            // embedded ole objects
            throw new \Exception('Not implemented: saving emebed OLE content');
        } else {
            $attachment->setBody($this->getData());
        }

        return $attachment;
    }

    public function toMimeString(): string
    {
        return (string) $this->toMime();
    }

    public function copyMimeToStream($stream): void
    {
        fwrite($stream, $this->toMimeString());
    }
}
