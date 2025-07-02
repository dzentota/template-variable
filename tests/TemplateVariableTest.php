<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable\Tests;

use PHPUnit\Framework\TestCase;
use Dzentota\TemplateVariable\TemplateVariable;
use Dzentota\TemplateVariable\Context\EscapeContext;
use Dzentota\TemplateVariable\Value\SimpleTypedValue;

/**
 * Basic tests for TemplateVariable functionality
 */
class TemplateVariableTest extends TestCase
{
    public function testToStringMagicMethod(): void
    {
        $var = new TemplateVariable('<script>alert("xss")</script>');
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
        
        $this->assertEquals($expected, (string) $var);
        $this->assertEquals($expected, $var->__toString());
    }
    
    public function testInvokeMagicMethodWithHtmlContext(): void
    {
        $var = new TemplateVariable('<b>Bold</b>');
        $expected = '&lt;b&gt;Bold&lt;/b&gt;';
        
        $this->assertEquals($expected, $var('html'));
    }
    
    public function testInvokeMagicMethodWithAttributeContext(): void
    {
        $var = new TemplateVariable('class="malicious" onclick="alert(1)"');
        $expected = 'class=&quot;malicious&quot; onclick=&quot;alert(1)&quot;';
        
        $this->assertEquals($expected, $var('attr'));
    }
    
    public function testInvokeMagicMethodWithJavaScriptContext(): void
    {
        $var = new TemplateVariable('</script><script>alert("xss")</script>');
        $result = $var('js');
        
        // Should be properly JSON encoded
        $this->assertStringContainsString('\\/', $result);
        $this->assertJson($result);
    }
    
    public function testInvokeMagicMethodWithUrlContext(): void
    {
        $var = new TemplateVariable('hello world & special chars!');
        $expected = 'hello%20world%20%26%20special%20chars%21';
        
        $this->assertEquals($expected, $var('url'));
    }
    
    public function testInvokeMagicMethodWithRawContext(): void
    {
        $value = '<b>Bold</b>';
        $var = new TemplateVariable($value);
        
        $this->assertEquals($value, $var('raw'));
    }
    
    public function testGetRawMethod(): void
    {
        $value = '<script>alert("xss")</script>';
        $var = new TemplateVariable($value);
        
        $this->assertEquals($value, $var->getRaw());
    }
    
    public function testWithTypedValue(): void
    {
        $typedValue = SimpleTypedValue::unsafe('<script>alert("xss")</script>', 'string');
        $var = new TemplateVariable($typedValue);
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
        
        $this->assertEquals($expected, (string) $var);
    }
    
    public function testWithSafeTypedValue(): void
    {
        $typedValue = SimpleTypedValue::safe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', 'string');
        $var = new TemplateVariable($typedValue);
        
        // Should still escape since we're targeting a specific context
        $this->assertStringContainsString('&lt;', (string) $var);
    }
    
    public function testStaticFactoryMethods(): void
    {
        $htmlVar = TemplateVariable::html('<p>Paragraph</p>');
        $attrVar = TemplateVariable::attr('value="test"');
        $jsVar = TemplateVariable::js('var x = "data";');
        $urlVar = TemplateVariable::url('hello world');
        $rawVar = TemplateVariable::raw('<em>Not escaped</em>');
        
        $this->assertEquals('&lt;p&gt;Paragraph&lt;/p&gt;', (string) $htmlVar);
        $this->assertEquals('value=&quot;test&quot;', (string) $attrVar);
        $this->assertJson((string) $jsVar);
        $this->assertEquals('hello%20world', (string) $urlVar);
        $this->assertEquals('<em>Not escaped</em>', (string) $rawVar);
    }
    
    public function testNeedsEscaping(): void
    {
        $safeVar = new TemplateVariable('Simple text');
        $unsafeVar = new TemplateVariable('<script>alert(1)</script>');
        
        $this->assertFalse($safeVar->needsEscaping('html'));
        $this->assertTrue($unsafeVar->needsEscaping('html'));
        $this->assertFalse($safeVar->needsEscaping('raw'));
        $this->assertFalse($unsafeVar->needsEscaping('raw'));
    }
    
    public function testInvalidContext(): void
    {
        $var = new TemplateVariable('test');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown escape context: invalid');
        
        $var('invalid');
    }
    
    public function testDifferentDefaultContext(): void
    {
        $var = new TemplateVariable('<script>alert("xss")</script>', EscapeContext::JAVASCRIPT);
        
        // Default context should be JavaScript, so toString should JSON encode
        $result = (string) $var;
        $this->assertJson($result);
    }
} 