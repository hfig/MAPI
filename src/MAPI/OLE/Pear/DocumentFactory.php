<?php

namespace Hfig\MAPI\OLE\Pear;

use Hfig\MAPI\OLE\CompoundDocumentFactory;
use Hfig\MAPI\OLE\CompoundDocumentElement;

use OLE;

class DocumentFactory implements CompoundDocumentFactory
{
    public function createFromFile($file): CompoundDocumentElement
    {
        $ole = new OLE();
        $ole->read($file);

        return new DocumentElement($ole, $ole->root);
    }

    public function createFromStream($stream): CompoundDocumentElement
    {
        // PHP buffering appears to prevent us using this wrapper - sometimes fseek() is not called
        //$wrappedStreamUrl = StreamWrapper::wrapStream($stream, 'r');

        $ole = new OLE();
        $ole->readStream($stream);

        return new DocumentElement($ole, $ole->root);
    }
}