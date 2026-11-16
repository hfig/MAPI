<?php

namespace Hfig\MAPI\Tests\OLE\RTF;

use Hfig\MAPI\OLE\RTF\EmbeddedHTML;
use PHPUnit\Framework\TestCase;

class EmbeddedHTMLTest extends TestCase
{
    public function testExtractsHtml()
    {
        $rtf = '{\rtf1\ansi\fromhtml1 {\*\htmltag19 <html>}{\*\htmltag50 <body>}'
            . 'Gr\\\'fc\\\'dfe\par {\*\htmltag84 <b>}bold{\*\htmltag92 </b>}\tab x \{y\}'
            . '{\*\htmltag58 </body>}{\*\htmltag27 </html>}}';

        $this->assertSame(
            "<html><body>Gr\xfc\xdfe\r\n<b>bold</b>\tx {y}</body></html>",
            EmbeddedHTML::extract($rtf)
        );
    }

    public function testLargeInputIsExtractedInLinearTime()
    {
        // Messages with embedded images carry several hundred kilobytes of plain
        // text inside the HTML. Each character used to make every regex search
        // the whole rest of the buffer, so this took minutes instead of milliseconds.
        $payload = str_repeat('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk', 6000);
        $rtf = '{\rtf1\ansi\fromhtml1 {\*\htmltag84 <img src="data:image/png;base64,}'
            . $payload . '{\*\htmltag84 ">}}';

        $start = microtime(true);
        $html = EmbeddedHTML::extract($rtf);
        $duration = microtime(true) - $start;

        $this->assertSame('<img src="data:image/png;base64,' . $payload . '">', $html);
        $this->assertLessThan(5, $duration, sprintf('Extraction of %d bytes took %.1f s', strlen($rtf), $duration));
    }

    public function testManyControlSymbolsAreExtractedInLinearTime()
    {
        // same as above, but every second character goes through scanRegex()
        $rtf = '{\rtf1\ansi\fromhtml1 {\*\htmltag84 <p>}' . str_repeat('x\{\par ', 40000) . '}';

        $start = microtime(true);
        $html = EmbeddedHTML::extract($rtf);
        $duration = microtime(true) - $start;

        $this->assertSame('<p>' . rtrim(str_repeat("x{\r\n", 40000)), $html);
        $this->assertLessThan(5, $duration, sprintf('Extraction of %d bytes took %.1f s', strlen($rtf), $duration));
    }
}
