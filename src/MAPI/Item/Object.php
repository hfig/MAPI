<?php

namespace Hfig\MAPI\Item;

class Object
{
    protected $properties;

    public function __construct($properties)
    {
        $this->properties = $properties;
    }


}