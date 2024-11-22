<?php

namespace Hfig\MAPI\OLE;

interface CompoundDocumentFactory
{
    public function createFromFile($file): CompoundDocumentElement;

    public function createFromStream($stream): CompoundDocumentElement;
}
