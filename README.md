# Hfig/MAPI

## Introduction
``Hfig/MAPI`` is a PHP library for reading and working with Microsoft Outlook/Exchange format email messages (``.msg`` files, aka MAPI documents).

The library can parse MAPI documents, and programatically extract the properties and streams of the document.

It can be used to convert messages to ``RFC822`` (MIME) format by utilising the [``Swiftmailer/Switfmailer``](https://github.com/swiftmailer/swiftmailer) library.

The library is ostensibly a port of the [``aquasync/ruby-msg``](https://github.com/aquasync/ruby-msg) library from the Ruby language. Some questionable PHP architectural decisions come from migrating Ruby constructs. Some awful, but functional, code comes from a direct migration of the Ruby library.

Compared to ``ruby-msg``, this library:

* Does not implement a command line entry point for message conversion
* Only handles MAPI documents in ``.msg`` files (or a PHP stream of ``.msg`` file data)
* Does not implement the conversion of RTF-format message bodies to plain text or HTML
* Has better support for decoding MAPI document properties
* Produces a more faithful MIME conversion of the MAPI document

## Installation

Install using composer

```sh
composer require hfig/mapi

# needed if you want to convert to MIME format
composer require swiftmailer/swiftmailer
```

## Usage

### Accessing document properties

```php
require 'vendor/autoload.php';

use Hfig\MAPI;
use Hfig\MAPI\OLE\Pear;

// message parsing and file IO are kept separate
$messageFactory = new MAPI\MapiMessageFactory();
$documentFactory = new Pear\DocumentFactory(); 

$ole = $documentFactory->createFromFile('source-file.msg');
$message = $messageFactory->parseMessage($ole);

// raw properties are available from the "properties" member
echo $message->properties['subject'], "\n";

// some properties have helper methods
echo $message->getSender(), "\n";
echo $message->getBody(), "\n";

// recipients and attachments are composed objects
foreach ($message->getRecipients() as $recipient) {
    // eg "To: John Smith <john.smith@example.com>
    echo sprintf('%s: %s', $recipient->getType(), (string)$recipient), "\n";
}
```

### Conversion to MIME
```php
require 'vendor/autoload.php';

use Hfig\MAPI;
use Hfig\MAPI\OLE\Pear;
use Hfig\MAPI\Mime\Swiftmailer;

$messageFactory = new MAPI\MapiMessageFactory(new Swiftmailer\Factory());
$documentFactory = new Pear\DocumentFactory(); 

$ole = $documentFactory->createFromFile('source-file.msg');
$message = $messageFactory->parseMessage($ole);

// returns a \Swift_Message object representaiton of the email
$mime = $message->toMime();

// or write it to file
$fd = fopen('dest-file.eml', 'w');
$message->copyMimeToStream($fd);
```

## Property Names

MAPI property names are documented by Microsoft in an inscrutible manner at https://docs.microsoft.com/en-us/previous-versions/office/developer/office-2007/cc815517(v%3doffice.12). 

A list of property names available for use in this library is included in the ``MAPI/Schema/MapiFieldsMessage.yaml`` file.

Keeping with the convention of the ``ruby-msg`` library, message properties are converted to a _nice_ name:

* ``PR_DISPLAY_NAME`` => ``display_name``
* ``PR_ATTACH_FILENAME`` => ``attach_filename``
* etc

## About MAPI Documents

MAPI documents are Microsoft OLE Structured Storage databases, much like old ``.doc``, ``.xls`` and ``.ppt`` files. They consist of an internal directory structure of streams of 4K blocks that resemble a virtual FAT filesystem. For economy reasons, every structured storage database contains a root stream which contains 64-byte blocks which in turn stores small pieces of data. For further information see [Microsoft's documentation](https://docs.microsoft.com/en-us/windows/desktop/Stg/structured-storage-start-page).

The PEAR library ``OLE`` can read these database files. However this PEAR library is ancient and does not meet any modern coding standards, hence it's kept entirely decoupled from the message parsing code of this library. Hopefully it can be replaced one day.

## Alternatives

For PHP, installing the [Kopano Core](https://github.com/Kopano-dev/kopano-core) project on your server will make available ``ext-mapi``, a PHP extension which implements allows access to a port of the low-level MAPI Win32 API.

See also:
* [``Email::Outlook::Message``](https://github.com/mvz/email-outlook-message-perl) (Perl)
* [``aquasync/ruby-msg``](https://github.com/aquasync/ruby-msg) (Ruby)
* [``JTNEF``](https://www.freeutils.net/source/jtnef/) (Java)
