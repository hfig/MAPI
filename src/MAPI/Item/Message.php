<?php

namespace Hfig\MAPI\Item;

// # IMessage essentially, but there's also stuff like IMAPIFolder etc. so, for this to form
// # basis for PST Item, it'd need to be more general.

abstract class Message extends MapiObject
{
    abstract public function getAttachments();

    abstract public function getRecipients();
}
