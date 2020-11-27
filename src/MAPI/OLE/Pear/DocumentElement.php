<?php

namespace Hfig\MAPI\OLE\Pear;

use Hfig\MAPI\OLE\CompoundDocumentElement;
use OLE;
use OLE_PPS;
use OLE_PPS_Root;

class DocumentElement implements CompoundDocumentElement
{
    /** @var OLE_PPS */
    private $pps;

    /** @var OLE */
    private $ole;

    /** @var DocumentElementCollection */
    //private $wrappedChildren;

    // the OLE file reference is required because the member ->ole on the PPS
    // element is never actually set (ie is a bug in PEAR::OLE)
    public function __construct(OLE $file, OLE_PPS $pps)
    {
        $this->pps = $pps;
        $this->ole = $file;
        //$this->wrappedChildren = null;
    }

    public function getIndex()
    {
        return $this->pps->No;
    }

    public function setIndex($index)
    {
        $this->pps->No = $index;
    }

    public function getName()
    {
        return $this->pps->Name;
    }

    public function setName($name)
    {
        $this->pps->Name = $name;
    }

    public function getType()
    {
        static $map = [
            OLE_PPS_TYPE_ROOT =>  CompoundDocumentElement::TYPE_ROOT,
            OLE_PPS_TYPE_DIR  =>  CompoundDocumentElement::TYPE_DIRECTORY,
            OLE_PPS_TYPE_FILE =>  CompoundDocumentElement::TYPE_FILE,
        ];

        return $map[$this->pps->Type] ?? null;
    }

    public function setType($type)
    {
        static $map = [
            CompoundDocumentElement::TYPE_ROOT => OLE_PPS_TYPE_ROOT,
            CompoundDocumentElement::TYPE_DIRECTORY => OLE_PPS_TYPE_DIR,
            CompoundDocumentElement::TYPE_FILE => OLE_PPS_TYPE_FILE ,
        ];

        if (!isset($map[$type])) {
            throw new \InvalidArgumentException(sprintf('Unknown document element type "%d"', $type));
        }

        $this->pps->Type = $map[$type];
    }

    public function isDirectory() 
    {
        return ($this->getType() == CompoundDocumentElement::TYPE_DIRECTORY);
    }

    public function isFile() 
    {
        return ($this->getType() == CompoundDocumentElement::TYPE_FILE);
    }

    public function isRoot() 
    {
        return ($this->getType() == CompoundDocumentElement::TYPE_ROOT);
    }
    
    public function getPreviousIndex()
    {
        return $this->pps->PrevPps;
    }

    public function setPreviousIndex($index)
    {
        $this->pps->PrevPps = $index;
    }

    public function getNextIndex()
    {
        return $this->pps->NextPps;
    }

    public function setNextIndex($index)
    {
        $this->pps->NextPps = $index;
    }
 
    public function getFirstChildIndex()
    {
        return $this->pps->DirPps;
    }

    public function setFirstChildIndex($index)
    {
        $this->pps->DirPps = $index;
    }

    public function getTimeCreated()
    {
        return $this->pps->Time1st;
    }

    public function setTimeCreated($time)
    {
        $this->pps->Time1st = $time;
    }

    public function getTimeModified()
    {
        return $this->pps->Time2nd;
    }

    public function setTimeModified($time)
    {
        $this->pps->Time2nd = $time;
    }
 
    // private, so no setter interface
    public function getStartBlock()
    {
        return $this->pps->_StartBlock;
    }
    
    public function getSize()
    {
        return $this->pps->Size;
    }

    public function setSize($size)
    {
        $this->pps->Size = $size;
    }

    /**
     * @return DocumentElementCollection
     */
    public function getChildren()
    {
        //if (!$this->wrappedChildren) {
        //    $this->wrappedChildren = new DocumentElementCollection($this->ole, $this->pps->Children);
        //}
        //return $this->wrappedChildren;

        return new DocumentElementCollection($this->ole, $this->pps->children);
    }

    public function getData()
    {
        //echo sprintf('Reading data for %s: index: %d, start: 0, length: %d'."\n", $this->getName(), $this->getIndex(), $this->getSize());

        return $this->ole->getData($this->getIndex(), 0, $this->getSize());
    }

    public function unwrap()
    {
        return $this->pps;
    }

    public function saveToStream($stream)
    {
        

        $root = new OLE_PPS_Root($this->pps->Time1st, $this->pps->Time2nd, $this->pps->children);

        // nasty Pear_OLE actually writes out a temp file and fpassthru's on it. Yuck.
        // so let's give a wrapped stream which ignores Pear_OLE's fopen() and fclose()
        $wrappedStreamUrl = StreamWrapper::wrapStream($stream, 'r');
        $root->save($wrappedStreamUrl);

        /*ob_start();
        try {
            $root->save('');
            fwrite($stream, ob_get_clean());
        }
        finally {
            ob_end_clean();
        }*/
    }
}