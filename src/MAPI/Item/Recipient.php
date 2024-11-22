<?php

namespace Hfig\MAPI\Item;

class Recipient extends MapiObject implements \Stringable
{
    public const RECIPIENT_TYPES = [
        0 => 'From',
        1 => 'To',
        2 => 'Cc',
        3 => 'Bcc',
    ];

    // # some kind of best effort guess for converting to standard mime style format.
    // # there are some rules for encoding non 7bit stuff in mail headers. should obey
    // # that here, as these strings could be unicode
    // # email_address will be an EX:/ address (X.400?), unless external recipient. the
    // # other two we try first.
    // # consider using entry id for this too.
    public function getName(): ?string
    {
        $name = $this->properties['transmittable_display_name'] ?? $this->properties['display_name'] ?? '';

        return preg_replace('/^\'(.*)\'/', '\1', (string) $name);
    }

    public function getEmail()
    {
        return $this->properties['smtp_address'] ?? $this->properties['org_email_addr'] ?? $this->properties['email_address'] ?? '';
    }

    public function getType()
    {
        $type = $this->properties['recipient_type'];

        return static::RECIPIENT_TYPES[$type] ?? $type;
    }

    public function getAddressType()
    {
        $type = $this->properties['addrtype'] ?? 'Unknown';

        return $type;

        /*if ($this->properties['smtp_address']) {
            return 'SMTP';
        }
        if ($this->properties['org_email_addr']) {
            return 'ORG';
        }
        if ($this->properties['email_address']) {
            return 'MAPI';
        }
        return 'Unknown';*/
    }

    public function __toString(): string
    {
        $name  = $this->getName();
        $email = $this->getEmail();

        // echo $this->getAddressType() . ': ' . sprintf('%s <%s>', $name, unpack('H*', $email)[1]) . "\n";

        if ($name && $name != $email) {
            return sprintf('%s <%s>', $name, $email);
        }

        return (string) ($email ?: $name);
    }
}
