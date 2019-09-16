<?php

namespace Hfig\MAPI\Tests;

use Hfig\MAPI\OLE\Pear\DocumentFactory;
use Hfig\MAPI\MapiMessageFactory;
use PHPUnit\Framework\TestCase;

class MapiMessageFactoryTest extends TestCase
{
    public function testParseMessage()
    {
        $documentFactory = new DocumentFactory();
        $messageFactory = new MapiMessageFactory();

        $ole = $documentFactory->createFromFile(__DIR__.'/../_files/sample.msg');

        $message = $messageFactory->parseMessage($ole);

        $this->assertEquals('Testing Manuel Lemos\' MIME E-mail composing and sending PHP class: HTML message',$message->properties['subject']);
        $this->assertEquals(
            "Testing Manuel Lemos' MIME E-mail composing and sending PHP class: HTML message\r\n________________________________\r\n\r\nHello Manuel,\r\n\r\nThis message is just to let you know that the MIME E-mail message composing and sending PHP class<http://www.phpclasses.org/mimemessage> is working as expected.\r\n\r\nHere is an image embedded in a message as a separate part:\r\n[cid:ae0357e57f04b8347f7621662cb63855.gif]\r\nThank you,\r\nmlemos\r\n\r\n",
            $message->getBody()
        );
        $attachments = $message->getAttachments();
        $this->assertCount(3,$attachments);

        $this->assertEquals('attachment.txt',$attachments[0]->getFilename());
        $this->assertEquals('This is just a plain text attachment file named attachment.txt .',$attachments[0]->getData());
        $this->assertEquals('logo.gif',$attachments[1]->getFilename());
        $this->assertEquals('background.gif',$attachments[2]->getFilename());
    }
}
