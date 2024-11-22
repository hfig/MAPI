<?php

namespace Hfig\MAPI\OLE\Pear;

use Hfig\MAPI\OLE\CompoundDocumentElement;
use OLE;

class DocumentElement implements CompoundDocumentElement
{
    // ** @var DocumentElementCollection */
    // private $wrappedChildren;

    // the OLE file reference is required because the member ->ole on the PPS
    // element is never actually set (ie is a bug in PEAR::OLE)
    public function __construct(private readonly \OLE $ole, private readonly \OLE_PPS $pps)
    {
        // $this->wrappedChildren = null;
    }

    public function getIndex()
    {
        return $this->pps->No;
    }

    public function setIndex($index): void
    {
        $this->pps->No = $index;
    }

    public function getName()
    {
        return $this->pps->Name;
    }

    public function setName($name): void
    {
        $this->pps->Name = $name;
    }

    public function getType(): ?int
    {
        static $map = [
            OLE_PPS_TYPE_ROOT => CompoundDocumentElement::TYPE_ROOT,
            OLE_PPS_TYPE_DIR  => CompoundDocumentElement::TYPE_DIRECTORY,
            OLE_PPS_TYPE_FILE => CompoundDocumentElement::TYPE_FILE,
        ];

        return $map[$this->pps->Type] ?? null;
    }

    public function setType($type): void
    {
        static $map = [
            CompoundDocumentElement::TYPE_ROOT      => OLE_PPS_TYPE_ROOT,
            CompoundDocumentElement::TYPE_DIRECTORY => OLE_PPS_TYPE_DIR,
            CompoundDocumentElement::TYPE_FILE      => OLE_PPS_TYPE_FILE,
        ];

        if (!isset($map[$type])) {
            throw new \InvalidArgumentException(sprintf('Unknown document element type "%d"', $type));
        }

        $this->pps->Type = $map[$type];
    }

    public function isDirectory(): bool
    {
        return $this->getType() == CompoundDocumentElement::TYPE_DIRECTORY;
    }

    public function isFile(): bool
    {
        return $this->getType() == CompoundDocumentElement::TYPE_FILE;
    }

    public function isRoot(): bool
    {
        return $this->getType() == CompoundDocumentElement::TYPE_ROOT;
    }

    public function getPreviousIndex()
    {
        return $this->pps->PrevPps;
    }

    public function setPreviousIndex($index): void
    {
        $this->pps->PrevPps = $index;
    }

    public function getNextIndex()
    {
        return $this->pps->NextPps;
    }

    public function setNextIndex($index): void
    {
        $this->pps->NextPps = $index;
    }

    public function getFirstChildIndex()
    {
        return $this->pps->DirPps;
    }

    public function setFirstChildIndex($index): void
    {
        $this->pps->DirPps = $index;
    }

    public function getTimeCreated()
    {
        return $this->pps->Time1st;
    }

    public function setTimeCreated($time): void
    {
        $this->pps->Time1st = $time;
    }

    public function getTimeModified()
    {
        return $this->pps->Time2nd;
    }

    public function setTimeModified($time): void
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

    public function setSize($size): void
    {
        $this->pps->Size = $size;
    }

    public function getChildren(): DocumentElementCollection
    {
        // if (!$this->wrappedChildren) {
        //    $this->wrappedChildren = new DocumentElementCollection($this->ole, $this->pps->Children);
        // }
        // return $this->wrappedChildren;

        return new DocumentElementCollection($this->ole, $this->pps->children);
    }

    public function getData(): string
    {
        // echo sprintf('Reading data for %s: index: %d, start: 0, length: %d'."\n", $this->getName(), $this->getIndex(), $this->getSize());

        return $this->ole->getData($this->getIndex(), 0, $this->getSize());
    }

    public function unwrap(): \OLE_PPS
    {
        return $this->pps;
    }

    public function saveToStream($stream): void
    {
        $root = new \OLE_PPS_Root($this->pps->Time1st, $this->pps->Time2nd, $this->pps->children);

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
