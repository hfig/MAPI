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
        $this->assertEquals('<20050430192829.0489.mlemos@acm.org>',$message->getInternetMessageId());

        $attachments = $message->getAttachments();
        $this->assertCount(3,$attachments);

        $this->assertEquals('attachment.txt',$attachments[0]->getFilename());
        $this->assertNull($attachments[0]->getContentId());
        $this->assertEquals('This is just a plain text attachment file named attachment.txt .',$attachments[0]->getData());

        $this->assertEquals('logo.gif',$attachments[1]->getFilename());
        $this->assertEquals('ae0357e57f04b8347f7621662cb63855.gif',$attachments[1]->getContentId());

        $this->assertEquals('background.gif',$attachments[2]->getFilename());
        $this->assertEquals('4c837ed463ad29c820668e835a270e8a.gif',$attachments[2]->getContentId());

        $this->assertEquals(new \DateTime('2005-04-30 22:28:29', new \DateTimeZone('UTC')), $message->getSendTime());
    }

    public function testParseMessage2()
    {
        $documentFactory = new DocumentFactory();
        $messageFactory = new MapiMessageFactory();

        $ole = $documentFactory->createFromFile(__DIR__.'/../_files/Swetlana.msg');

        $message = $messageFactory->parseMessage($ole);

        $this->assertEquals(new \DateTime('2006-03-07 13:25:19', new \DateTimeZone('UTC')), $message->getSendTime());
    }
}
