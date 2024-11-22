<?php

namespace Hfig\MAPI\Mime;

interface MimeConvertible
{
    public function toMime();

    public function toMimeString(): string;

    public function copyMimeToStream($stream);
}
