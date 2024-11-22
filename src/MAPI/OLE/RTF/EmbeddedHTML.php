<?php

namespace Hfig\MAPI\OLE\RTF;

class EmbeddedHTML
{
    // the fact that this seems to work is rather amazing because it's a horrid mess!
    // the proper format is specified by [MS-OXRTFEX]

    public static function extract($data): string
    {
        if ($pos = !str_contains((string) $data, '{\*\htmltag')) {
            return '';
        }

        $html      = '';
        $ignoreTag = '';

        $scanner = new StringScanner($data);
        // fix cf ruby-msg - skip the \htmltag element's parameter
        if ($scanner->scanUntilRegex('/\x5c\*\x5chtmltag(\d+) ?/') === false) {
            return '';
        }

        while (!$scanner->eos()) {
            // echo 'next 40 ' .  str_pad(str_replace(["\r","\n"], '', trim(substr((string)$scanner, 0, 40))), 40) . '          ';

            if ($scanner->scan('{')) {
                // echo 'skip {';
            } elseif ($scanner->scan('}')) {
                // echo 'skip }';
            } elseif ($scanner->scanRegex('/\x5c\*\x5chtmltag(\d+) ?/')) {
                if ($ignoreTag == $scanner->result()[1][0]) {
                    // echo 'duplicate. skip to }';
                    $scanner->scanUntil('}');
                    $ignoreTag = '';
                }
            } elseif ($scanner->scanRegex('/\x5c\*\x5cmhtmltag(\d+) ?/')) {
                // echo 'set ignore on this';
                $ignoreTag = $scanner->result()[1][0];
            }
            // fix cf ruby-msg - negative lookahead of \par elements so we don't match \pard
            elseif ($scanner->scanRegex('/\x5cpar(?!\w) ?/')) {
                // echo 'CRLF';
                $html .= "\r\n";
            } elseif ($scanner->scanRegex('/\x5ctab ?/')) {
                // echo 'Tab';
                $html .= "\t";
            } elseif ($scanner->scanRegex('/\x5c\'([0-9A-Za-z]{2})/')) {
                // echo 'Append char' . $scanner->result()[1][0];
                $html .= chr(hexdec((string) $scanner->result()[1][0]));
            } elseif ($scanner->scan('\pntext')) {
                // echo 'skip to }';
                $scanner->scanUntil('}');
            } elseif ($scanner->scanRegex('/\x5chtmlrtf1? ?/')) {
                // echo 'skip to htmlrtf0';
                $scanner->scanUntilRegex('/\x5chtmlrtf0 ?/');
            }
            // # a generic throw away unknown tags thing.
            // # the above 2 however, are handled specially
            elseif ($scanner->scanRegex('/\x5c[a-z-]+(\d+)? ?/')) {
                // echo 'skip unknown tag';
            }
            // #elseif ($scanner->scanRegex('/\\li(\d+) ?/')) {}
            // #elseif ($scanner->scanRegex('/\\fi-(\d+) ?/')) {}
            elseif ($scanner->scanRegex('/\r?\n/')) {
                // echo 'data CRLF';
            } elseif ($scanner->scanRegex('/\x5c({|}|\x5c)/')) {
                // echo 'append special char';
                $html .= $scanner->result()[1][0];
            } else {
                // echo 'append';

                $html .= $scanner->increment();
            }

            // echo '    ' . substr($html, -20) . "\n";
        }

        return trim($html);
    }
}
