<?php

namespace Hfig\MAPI\Message;

use Hfig\MAPI\Item\Recipient as RecipientItem;
use Hfig\MAPI\OLE\CompoundDocumentElement as Element;
use Hfig\MAPI\Property\PropertySet;
use Hfig\MAPI\Property\PropertyStore;

class Recipient extends RecipientItem
{
    protected Element $obj;

    /** @var PropertySet */
    protected $properties;

    public function __construct(Element $obj, Message $parent)
    {
        $this->obj = $obj;

        // initialise property set
        $this->properties = new PropertySet(
            new PropertyStore($obj, $parent->getNameId()),
        );
    }

    public function __get($name)
    {
        if ($name == 'properties') {
            return $this->properties;
        }

        return null;
    }
}
