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

        $fh = $stream;
        $ole = new OLE();

        {
            $ole->_file_handle = $fh;

            $signature = fread($fh, 8);
            if ("\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" != $signature) {
                return $ole->raiseError("File doesn't seem to be an OLE container.");
            }
            fseek($fh, 28);
            if (fread($fh, 2) != "\xFE\xFF") {
                // This shouldn't be a problem in practice
                return $ole->raiseError("Only Little-Endian encoding is supported.");
            }
            // Size of blocks and short blocks in bytes
            $ole->bigBlockSize   = pow(2, $ole->_readInt2($fh));
            $ole->smallBlockSize = pow(2, $ole->_readInt2($fh));

            // Skip UID, revision number and version number
            fseek($fh, 44);
            // Number of blocks in Big Block Allocation Table
            $bbatBlockCount = $ole->_readInt4($fh);

            // Root chain 1st block
            $directoryFirstBlockId = $ole->_readInt4($fh);

            // Skip unused bytes
            fseek($fh, 56);
            // Streams shorter than this are stored using small blocks
            $ole->bigBlockThreshold = $ole->_readInt4($fh);
            // Block id of first sector in Short Block Allocation Table
            $sbatFirstBlockId = $ole->_readInt4($fh);
            // Number of blocks in Short Block Allocation Table
            $sbbatBlockCount = $ole->_readInt4($fh);
            // Block id of first sector in Master Block Allocation Table
            $mbatFirstBlockId = $ole->_readSignedInt4($fh);
            // Number of blocks in Master Block Allocation Table
            $mbbatBlockCount = $ole->_readInt4($fh);
            $ole->bbat = array();

            // Remaining 4 * 109 bytes of current block is beginning of Master
            // Block Allocation Table
            $mbatBlocks = array();
            for ($i = 0; $i < 109; $i++) {
                $mbatBlocks[] = $ole->_readSignedInt4($fh);
            }

            // Read rest of Master Block Allocation Table (if any is left)
            $pos = $ole->_getBlockOffset($mbatFirstBlockId);
            for ($i = 0; $i < $mbbatBlockCount; $i++) {
                fseek($fh, $pos);
                for ($j = 0; $j < $ole->bigBlockSize / 4 - 1; $j++) {
                    $mbatBlocks[] = $ole->_readInt4($fh);
                }
                // Last block id in each block points to next block
                $pos = $ole->_getBlockOffset($ole->_readInt4($fh));
            }

            // Read Big Block Allocation Table according to chain specified by
            // $mbatBlocks
            for ($i = 0; $i < $bbatBlockCount; $i++) {
                $pos = $ole->_getBlockOffset($mbatBlocks[$i]);
                fseek($fh, $pos);
                for ($j = 0 ; $j < $ole->bigBlockSize / 4; $j++) {
                    $ole->bbat[] = $ole->_readSignedInt4($fh);
                }
            }

            // Read short block allocation table (SBAT)
            $ole->sbat = array();
            $shortBlockCount = $sbbatBlockCount * $ole->bigBlockSize / 4;
            $sbatFh = $ole->getStream($sbatFirstBlockId);
            if (!$sbatFh) {
                // Avoid an infinite loop if ChainedBlockStream.php somehow is
                // missing
                return false;
            }
            for ($blockId = 0; $blockId < $shortBlockCount; $blockId++) {
                $ole->sbat[$blockId] = $ole->_readSignedInt4($sbatFh);
            }
            fclose($sbatFh);

            $ole->_readPpsWks($directoryFirstBlockId);

        }



        return new DocumentElement($ole, $ole->root);
    }
}