<?php

declare(strict_types=1);

namespace Tests;

use Dzentota\TemplateVariable\Context\EscapeContext;
use Dzentota\TemplateVariable\Escaper\ContextAwareEscaper;
use Dzentota\TemplateVariable\TemplateVariable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlAttributeContextTest extends TestCase
{
    public static function xssPayloads(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'mixed case' => ['JaVaScRiPt:alert(1)'],
            'leading spaces' => ['   javascript:alert(1)'],
            'leading control' => ["\x01javascript:alert(1)"],
            'tab in scheme' => ["java\tscript:alert(1)"],
            'newline in scheme' => ["java\nscript:alert(1)"],
            'cr in scheme' => ["java\rscript:alert(1)"],
            'null byte' => ["java\0script:alert(1)"],
            'entity encoded colon' => ['javascript&colon;alert(1)'],
            'numeric entity' => ['&#106;avascript:alert(1)'],
            'hex entity' => ['&#x6A;avascript:alert(1)'],
            'percent-encoded' => ['javascript%3Aalert(1)'],
            'data html' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
            'DATA upper' => ['DATA:text/html,<script>alert(1)</script>'],
            'vbscript' => ['vbscript:msgbox(1)'],
            'protocol relative' => ['//evil.example/x'],
            'backslash protocol relative' => ['\\\\evil.example/x'],
            'slash backslash' => ['/\\evil.example/x'],
            'unknown scheme' => ['file:///etc/passwd'],
            'unicode whitespace' => ["\u{00A0}javascript:alert(1)"],
            'zero width' => ["java\u{200B}script:alert(1)"],
        ];
    }

    #[DataProvider('xssPayloads')]
    public function testUnsafeUrlsAreReplaced(string $payload): void
    {
        $escaper = new ContextAwareEscaper();
        $this->assertSame('#', $escaper->escape($payload, EscapeContext::URL_ATTR));
        $this->assertSame('#', TemplateVariable::create($payload)('href'));
    }

    public static function safeUrls(): array
    {
        return [
            ['https://example.com/a?b=1&c=2', 'https://example.com/a?b=1&amp;c=2'],
            ['HTTP://Example.com', 'HTTP://Example.com'],
            ['mailto:a@example.com', 'mailto:a@example.com'],
            ['/path/to"x', '/path/to&quot;x'],
            ['relative/page.html', 'relative/page.html'],
            ['../up', '../up'],
            ['#frag', '#frag'],
            ['?q=1', '?q=1'],
            ['  https://example.com  ', 'https://example.com'],
            ['page:1', '#'],
            ['./page:1', './page:1'],
        ];
    }

    #[DataProvider('safeUrls')]
    public function testSafeUrlsAreAttributeEscaped(string $in, string $expected): void
    {
        $this->assertSame($expected, (new ContextAwareEscaper())->escape($in, EscapeContext::URL_ATTR));
    }

    public function testEmptyUrlBecomesPlaceholder(): void
    {
        $this->assertSame('#', TemplateVariable::create('   ')('url_attr'));
    }

    public function testProtocolRelativeConfigurable(): void
    {
        $escaper = new ContextAwareEscaper(allowProtocolRelative: true);
        $this->assertSame('//cdn.example/x.js', $escaper->escape('//cdn.example/x.js', EscapeContext::URL_ATTR));
        $this->assertSame('#', $escaper->escape('\\\\cdn.example/x.js', EscapeContext::URL_ATTR));
    }

    public function testCustomSchemesAndPlaceholder(): void
    {
        $escaper = new ContextAwareEscaper(allowedUrlSchemes: ['https', 'tel'], unsafeUrlPlaceholder: 'about:blank');
        $this->assertSame('tel:+123', $escaper->escape('tel:+123', EscapeContext::URL_ATTR));
        $this->assertSame('about:blank', $escaper->escape('http://x', EscapeContext::URL_ATTR));
    }

    public function testAliasesAndFactory(): void
    {
        foreach (['href', 'src', 'url_attr', 'urlattr'] as $alias) {
            $this->assertSame('#', TemplateVariable::create('javascript:x')($alias));
        }
        $this->assertSame('#', (string) TemplateVariable::urlAttr('javascript:x'));
        $this->assertSame(EscapeContext::URL_ATTR, EscapeContext::from('url_attr'));
    }

    public function testUrlComponentContextUnchanged(): void
    {
        $this->assertSame('hello%20world%26', TemplateVariable::create('hello world&')('url'));
    }

    public function testNeedsEscaping(): void
    {
        $e = new ContextAwareEscaper();
        $this->assertTrue($e->needsEscaping('javascript:x', EscapeContext::URL_ATTR));
        $this->assertFalse($e->needsEscaping('https://example.com/', EscapeContext::URL_ATTR));
    }
}
