<?php

namespace Hfig\MAPI\Message;

use Hfig\MAPI\Item\Attachment as AttachmentItem;
use Hfig\MAPI\OLE\CompoundDocumentElement as Element;
use Hfig\MAPI\Property\PropertyStore;
use Hfig\MAPI\Property\PropertySet;


/**
 * @var PropertySet $properties
 */
class Attachment extends AttachmentItem
{
    /** @var Element */
    protected $obj;

    /** @var Message */
    protected $parent;

    protected $embedded_ole_type;

    public function __construct(Element $obj, Message $parent)
    {
        $this->obj = $obj;
        $this->parent = $parent;

        $this->embedded_msg = null;
        $this->embedded_ole  = null;
        $this->embedded_ole_type = '';

        // Set properties
        parent::__construct(new PropertySet(
            new PropertyStore($obj, $parent->getNameId())
        ));

        // initialise property set
        //super PropertySet.new(PropertyStore.load(@obj))
        //Msg.warn_unknown @obj
        foreach ($obj->getChildren() as $child) {
            if ($child->isDirectory() && preg_match(PropertyStore::SUBSTG_RX, $child->getName(), $matches)) {
                // magic numbers??
                if ($matches[1] == '3701' && strtolower($matches[2]) == '000d') {
                    $this->embedded_ole = $child;
                }
            }

        }

        if ($this->embedded_ole) {
            $type = $this->checkEmbeddedOleType();
            if ($type == 'Microsoft Office Outlook Message') {
                $this->embedded_msg = new Message($this->embedded_ole, $parent);
            }
        }

    }

    protected function checkEmbeddedOleType()
    {
        $found = 0;
        $type = null;

        foreach ($this->embedded_ole->getChildren() as $child) {
            if (preg_match('/__(substg|properties|recip|attach|nameid)/', $child->getName())) {
                $found++;
                if ($found > 2) break;
            }
        }
        if ($found > 2) {
            $type = 'Microsoft Office Outlook Message';
        }

        if ($type) {
            $this->embedded_ole_type = $type;
        }

        return $type;
        
    }

    public function getMimeType()
    {

        $mime = $this->properties['attach_mime_tag'] ?? $this->embedded_ole_type;
        if (!$mime) {
            $mime = 'application/octet-stream';
        }
        

        return $mime;
    }

    public function getContentId(): ?string
    {
        return $this->properties['attach_content_id'] ?? null;
    }

    public function getEmbeddedOleData(): ?string
    {
        $compobj = $this->properties["\01CompObj"];
        if (is_null($compobj)) {
            return null;
        }
        return substr($compobj, 32);
    }

    public function isValid(): bool
    {
        return $this->properties !== null;
    }

    public function __get($name)
    {
        if ($name == 'properties') {
            return $this->properties;
        }

        return null;
    }
}