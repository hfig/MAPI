<?php

namespace Hfig\MAPI\Item;

class MapiObject
{
    protected $properties;

    public function __construct($properties)
    {
        $this->properties = $properties;
    }


}