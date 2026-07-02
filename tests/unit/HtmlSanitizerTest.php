<?php

use App\Libraries\HtmlSanitizer;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Security + correctness coverage for the paragraph HTML sanitizer. No DB.
 *
 * @internal
 */
final class HtmlSanitizerTest extends CIUnitTestCase
{
    private HtmlSanitizer $s;

    protected function setUp(): void
    {
        parent::setUp();
        $this->s = new HtmlSanitizer();
    }

    public function testKeepsBasicFormatting(): void
    {
        $out = $this->s->clean('<p>Hello <strong>bold</strong> and <em>italic</em></p>');
        $this->assertStringContainsString('<strong>bold</strong>', $out);
        $this->assertStringContainsString('<em>italic</em>', $out);
    }

    public function testKeepsListsAndHeadings(): void
    {
        $out = $this->s->clean('<h2>Title</h2><ul><li>one</li><li>two</li></ul>');
        $this->assertStringContainsString('<h2>Title</h2>', $out);
        $this->assertStringContainsString('<li>one</li>', $out);
    }

    public function testRemovesScriptEntirely(): void
    {
        $out = $this->s->clean('<p>ok</p><script>alert(1)</script>');
        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('alert(1)', $out);
        $this->assertStringContainsString('ok', $out);
    }

    public function testStripsEventHandlers(): void
    {
        $out = $this->s->clean('<p onclick="steal()">text</p>');
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringContainsString('text', $out);
    }

    public function testStripsUnsafeStyleAttribute(): void
    {
        $out = $this->s->clean('<p style="background:url(javascript:alert(1))">x</p>');
        $this->assertStringNotContainsString('style', $out);
        $this->assertStringNotContainsString('javascript', $out);
    }

    public function testKeepsOnlyTextAlignFromStyle(): void
    {
        $out = $this->s->clean('<p style="text-align: center; background: red;">c</p>');
        $this->assertStringContainsString('text-align: center', $out);
        $this->assertStringNotContainsString('background', $out);
        $this->assertStringNotContainsString('red', $out);
    }

    public function testRejectsInvalidTextAlignValue(): void
    {
        $out = $this->s->clean('<p style="text-align: expression(alert(1))">c</p>');
        $this->assertStringNotContainsString('style', $out);
        $this->assertStringNotContainsString('expression', $out);
    }

    public function testBlocksJavascriptHref(): void
    {
        $out = $this->s->clean('<a href="javascript:alert(1)">click</a>');
        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringContainsString('click', $out);
    }

    public function testKeepsSafeHref(): void
    {
        $out = $this->s->clean('<a href="https://example.com">link</a>');
        $this->assertStringContainsString('href="https://example.com"', $out);
    }

    public function testUnwrapsUnknownTagsKeepingText(): void
    {
        $out = $this->s->clean('<div><span>kept text</span></div>');
        $this->assertStringNotContainsString('<div', $out);
        $this->assertStringNotContainsString('<span', $out);
        $this->assertStringContainsString('kept text', $out);
    }

    public function testKeepsAllowedAlignmentClassOnly(): void
    {
        $out = $this->s->clean('<p class="ql-align-center evil-class">centered</p>');
        $this->assertStringContainsString('ql-align-center', $out);
        $this->assertStringNotContainsString('evil-class', $out);
    }

    public function testPlainTextPreserved(): void
    {
        $this->assertSame('just words', $this->s->clean('just words'));
    }

    public function testEmptyInput(): void
    {
        $this->assertSame('', $this->s->clean(''));
        $this->assertSame('', $this->s->clean('   '));
    }

    public function testNestedScriptInsideUnknownTag(): void
    {
        $out = $this->s->clean('<div>before<script>bad()</script>after</div>');
        $this->assertStringNotContainsString('bad()', $out);
        $this->assertStringContainsString('before', $out);
        $this->assertStringContainsString('after', $out);
    }

    public function testKeepsImageWithSafeSrcButStripsHandlers(): void
    {
        $out = $this->s->clean('<img src="/form-image/1/abc.png" alt="pic" onerror="alert(1)">');
        $this->assertStringContainsString('<img', $out);
        $this->assertStringContainsString('src="/form-image/1/abc.png"', $out);
        $this->assertStringContainsString('alt="pic"', $out);
        $this->assertStringNotContainsString('onerror', $out);
    }

    public function testDropsImageWithUnsafeSrc(): void
    {
        $out = $this->s->clean('<p>before</p><img src="javascript:alert(1)">');
        $this->assertStringNotContainsString('<img', $out);
        $this->assertStringNotContainsString('javascript', $out);
        $this->assertStringContainsString('before', $out);
    }

    public function testKeepsHttpsImage(): void
    {
        $out = $this->s->clean('<img src="https://cdn.example.com/logo.png">');
        $this->assertStringContainsString('src="https://cdn.example.com/logo.png"', $out);
    }

    public function testKeepsValidImageDimensions(): void
    {
        $out = $this->s->clean('<img src="/form-image/1/a.png" width="50%" height="200">');
        $this->assertStringContainsString('width="50%"', $out);
        $this->assertStringContainsString('height="200"', $out);
    }

    public function testStripsInvalidImageWidth(): void
    {
        $out = $this->s->clean('<img src="/form-image/1/a.png" width="javascript:alert(1)">');
        $this->assertStringContainsString('<img', $out);
        $this->assertStringNotContainsString('javascript', $out);
        $this->assertStringNotContainsString('width=', $out);
    }
}
