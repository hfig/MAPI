<?php

namespace Hfig\MAPI\Message;

use Hfig\MAPI\OLE\CompoundDocumentElement as Element;
use Hfig\MAPI\OLE\Guid\OleGuid;
use Hfig\MAPI\OLE\RTF;

use Hfig\MAPI\Item\Message as MessageItem;

use Hfig\MAPI\Property\PropertyStore;
use Hfig\MAPI\Property\PropertySet;


class Message extends MessageItem
{


    const ATTACH_RX     = '/^__attach_version1\.0_.*/';
	const RECIP_RX      = '/^__recip_version1\.0_.*/';
    const VALID_RX      = PropertyStore::VALID_RX + [
        self::ATTACH_RX,
        self::RECIP_RX
    ];


    /** @var Element */
    protected $obj;

    /** @var PropertySet */
    protected $properties;

    /** @var Message */
    protected $parent;

    /** @var Attachment[] */
    protected $attachments = [];
    /** @var Recipient[] */
    protected $recipients = [];

    protected $bodyPlain;
    protected $bodyRTF;
    protected $bodyHTML;



    public function __construct(Element $obj, ?Message $parent = null)
    {
        
        $this->obj = $obj;
        $this->parent = $parent;
        
        $this->properties = new PropertySet(
            new PropertyStore($obj, ($parent) ? $parent->getNameId() : null)
        );

        $this->buildAttachments();
        $this->buildRecipients();
        

    }

    

    protected function buildAttachments()
    {
        foreach ($this->obj->getChildren() as $child) {
            if ($child->isDirectory() && preg_match(self::ATTACH_RX, $child->getName())) {
                $attachment = new Attachment($child, $this);
                if ($attachment->isValid()) {
                    $this->attachments[] = $attachment;
                }
            }
        }
    }

    protected function buildRecipients()
    {
        foreach ($this->obj->getChildren() as $child) {
            if ($child->isDirectory() && preg_match(self::RECIP_RX, $child->getName())) {

                //echo 'Got child . ' . $child->getName() . "\n";

                $recipient = new Recipient($child, $this);
                $this->recipients[] = $recipient;
            }
        }
    }

    /** @return Attachment[] */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /** @return  Recipient[] */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getRecipientsOfType($type): array
    {
        $response = [];
        foreach ($this->recipients as $r) {
            if ($r->getType() == $type) {
                $response[] = $r;
            }
        }
        return $response;
    }

    public function getNameId()
    {
        return $this->properties->getStore()->getNameId();
    }

    public function getInternetMessageId(): ?string
    {
        return $this->properties['internet_message_id'] ?? null;
    }

    public function getBody()
    {
        if ($this->bodyPlain) return $this->bodyPlain;
        
        if ($this->properties['body']) {
            $this->bodyPlain = $this->properties['body'];
        }

        // parse from RTF
        if (!$this->bodyPlain) {
            //jstewmc/rtf
            throw new \Exception('No Plain Text body. Convert from RTF not implemented');
        }

        return $this->bodyPlain;
    }

    public function getBodyRTF()
    {
        if ($this->bodyRTF) return $this->bodyRTF;

        if ($this->properties['rtf_compressed']) {

            $this->bodyRTF = RTF\CompressionCodec::decode($this->properties['rtf_compressed']);
        }

        return $this->bodyRTF;
    }

    public function getBodyHTML()
    {
        if ($this->bodyHTML) return $this->bodyHTML;

        if ($this->properties['body_html']) {
            $this->bodyHTML = $this->properties['body_html'];
            
            if ($this->bodyHTML) {
                $this->bodyHTML = trim($this->bodyHTML);
            }
        }

        if (!$this->bodyHTML) {
            if ($rtf = $this->getBodyRTF()) {
                $this->bodyHTML = RTF\EmbeddedHTML::extract($rtf);
            }

            if (!$this->bodyHTML) {
                //jstewmc/rtf
                throw new \Exception('No HTML or Embedded RTF body. Convert from RTF not implemented');
            }
        }

        return $this->bodyHTML;
    }

    public function getSender()
    {
        $senderName = $this->properties['sender_name'];
        $senderAddr = $this->properties['sender_email_address'];
        $senderType = $this->properties['sender_addrtype'];

        $from = '';
        if ($senderType == 'SMTP') {
           $from = $senderAddr;
        }
        else {
            $from = $this->properties['sender_smtp_address'] ?? 
                    $this->properties['sender_representing_smtp_address'] ??
                    // synthesise??
                    // for now settle on type:address eg X400:<dn>
                    sprintf('%s:%s', $senderType, $senderAddr);
        }

        if ($senderName) {
            $from = sprintf('%s <%s>', $senderName, $from);
        }        
        
        return $from;
    }

    public function getSendTime(): ?\DateTime
    {
        $sendTime = $this->properties['client_submit_time'];

        if (!$sendTime) {
            return null;
        }

        return \DateTime::createFromFormat('U',$sendTime);
    }

    public function properties(): PropertySet
    {
	return $this->properties;
    }
	
    public function __get($name)
    {
        if ($name == 'properties') {
            return $this->properties;
        }

        return null;
    }



}
