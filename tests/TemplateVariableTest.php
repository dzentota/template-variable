<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Dzentota\TemplateVariable\TemplateVariable;
use Dzentota\TemplateVariable\Context\EscapeContext;
use Dzentota\TemplateVariable\Escaper\ContextAwareEscaper;

class TemplateVariableTest extends TestCase
{
    private ContextAwareEscaper $escaper;
    
    protected function setUp(): void
    {
        $this->escaper = new ContextAwareEscaper();
    }
    
    /**
     * Test automatic HTML escaping via __toString()
     */
    public function testAutoHtmlEscaping(): void
    {
        $maliciousInput = '<script>alert("XSS")</script>';
        $var = new TemplateVariable($maliciousInput);
        
        $expected = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';
        $this->assertEquals($expected, (string) $var);
        $this->assertEquals($expected, $var->__toString());
    }
    
    /**
     * Test explicit HTML context escaping
     */
    public function testExplicitHtmlEscaping(): void
    {
        $maliciousInput = '<script>alert("XSS")</script>';
        $var = new TemplateVariable($maliciousInput);
        
        $expected = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';
        $this->assertEquals($expected, $var('html'));
        $this->assertEquals($expected, $var('h'));
    }
    
    /**
     * Test HTML attribute context escaping
     */
    public function testAttributeEscaping(): void
    {
        // Realistic attack vector: attribute injection
        $userInput = '" onload="alert(1)" class="';
        $var = new TemplateVariable($userInput);
        
        $expected = '&quot; onload=&quot;alert(1)&quot; class=&quot;';
        $this->assertEquals($expected, $var('attr'));
        $this->assertEquals($expected, $var('attribute'));
        $this->assertEquals($expected, $var('a'));
    }
    
    /**
     * Test JavaScript context escaping
     */
    public function testJavaScriptEscaping(): void
    {
        $maliciousInput = '</script><script>alert("XSS")</script>';
        $var = new TemplateVariable($maliciousInput);
        
        // Should use flexible escaping without automatic quotes
        $result = $var('js');
        
        // Check that dangerous characters are properly escaped
        $this->assertStringContainsString('\\u003C', $result); // <
        $this->assertStringContainsString('\\u003E', $result); // >
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
    }
    
    /**
     * Test URL context escaping
     */
    public function testUrlEscaping(): void
    {
        $userInput = 'hello world & special chars';
        $var = new TemplateVariable($userInput);
        
        $expected = 'hello%20world%20%26%20special%20chars';
        $this->assertEquals($expected, $var('url'));
        $this->assertEquals($expected, $var('uri'));
        $this->assertEquals($expected, $var('u'));
    }
    
    /**
     * Test CSS context escaping
     */
    public function testCssEscaping(): void
    {
        $userInput = 'color: red; background: url("javascript:alert(1)")';
        $var = new TemplateVariable($userInput);
        
        $result = $var('css');
        
        // Should escape quotes and other CSS special characters
        $this->assertStringContainsString('\\"', $result);
        $this->assertStringNotContainsString('"javascript:alert(1)"', $result);
    }
    
    /**
     * Test raw context (no escaping)
     */
    public function testRawContext(): void
    {
        $userInput = '<script>alert("XSS")</script>';
        $var = new TemplateVariable($userInput);
        
        // Raw context should return the input unchanged
        $this->assertEquals($userInput, $var('raw'));
        $this->assertEquals($userInput, $var('none'));
        $this->assertEquals($userInput, $var('r'));
    }
    
    /**
     * Test static factory methods
     */
    public function testStaticFactoryMethods(): void
    {
        $input = '<test>';
        
        $htmlVar = TemplateVariable::html($input);
        $this->assertEquals('&lt;test&gt;', (string) $htmlVar);
        
        $attrVar = TemplateVariable::attr($input);
        $this->assertEquals('&lt;test&gt;', $attrVar('attr'));
        
        $jsVar = TemplateVariable::js($input);
        $this->assertStringContainsString('\\u003C', $jsVar('js'));
        
        $cssVar = TemplateVariable::css($input);
        $this->assertEquals('\\3C test\\3E ', $cssVar('css'));
        
        $urlVar = TemplateVariable::url($input);
        $this->assertEquals('%3Ctest%3E', $urlVar('url'));
        
        $rawVar = TemplateVariable::raw($input);
        $this->assertEquals('<test>', (string) $rawVar);
    }
    
    /**
     * Test getRaw method
     */
    public function testGetRaw(): void
    {
        $input = '<script>alert("test")</script>';
        $var = new TemplateVariable($input);
        
        $this->assertEquals($input, $var->getRaw());
    }
    
    /**
     * Test needsEscaping method
     */
    public function testNeedsEscaping(): void
    {
        $safeInput = 'hello world';
        $dangerousInput = '<script>alert("XSS")</script>';
        
        $safeVar = new TemplateVariable($safeInput);
        $dangerousVar = new TemplateVariable($dangerousInput);
        
        // Safe input shouldn't need escaping for HTML
        $this->assertFalse($safeVar->needsEscaping('html'));
        
        // Dangerous input should need escaping
        $this->assertTrue($dangerousVar->needsEscaping('html'));
        $this->assertTrue($dangerousVar->needsEscaping(EscapeContext::HTML));
        
        // Raw context never needs escaping
        $this->assertFalse($dangerousVar->needsEscaping('raw'));
    }
    
    /**
     * Test invalid context throws exception
     */
    public function testInvalidContextThrowsException(): void
    {
        $var = new TemplateVariable('test');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown escape context: invalid');
        
        $var('invalid');
    }
    
    /**
     * Test TypedValue integration with mock object
     */
    public function testTypedValueIntegration(): void
    {
        // Create a mock TypedValue object
        $typedValue = new class {
            public function toNative(): string {
                return '<script>alert("from TypedValue")</script>';
            }
        };
        
        $var = new TemplateVariable($typedValue);
        
        // Should extract value using toNative() and then escape it
        $expected = '&lt;script&gt;alert(&quot;from TypedValue&quot;)&lt;/script&gt;';
        $this->assertEquals($expected, (string) $var);
    }
    
    /**
     * Test scalar values are not escaped (not tainted)
     */
    public function testScalarValuesNotEscaped(): void
    {
        // Test different scalar types
        $boolVar = new TemplateVariable(true);
        $this->assertEquals('1', (string) $boolVar);
        
        $boolFalseVar = new TemplateVariable(false);
        $this->assertEquals('', (string) $boolFalseVar);
        
        $intVar = new TemplateVariable(42);
        $this->assertEquals('42', (string) $intVar);
        
        $floatVar = new TemplateVariable(3.14);
        $this->assertEquals('3.14', (string) $floatVar);
        
        $nullVar = new TemplateVariable(null);
        $this->assertEquals('', (string) $nullVar);
        
        // Scalar values should not need escaping
        $this->assertFalse($intVar->needsEscaping('html'));
        $this->assertFalse($floatVar->needsEscaping('html'));
        $this->assertFalse($boolVar->needsEscaping('html'));
        $this->assertFalse($nullVar->needsEscaping('html'));
    }
    
    /**
     * Test TypedValue with scalar result
     */
    public function testTypedValueWithScalarResult(): void
    {
        // TypedValue that returns a scalar
        $typedValue = new class {
            public function toNative(): int {
                return 42;
            }
        };
        
        $var = new TemplateVariable($typedValue);
        
        // Should return scalar as string without escaping
        $this->assertEquals('42', (string) $var);
        $this->assertFalse($var->needsEscaping('html'));
    }
    
    /**
     * Test Stringable objects
     */
    public function testStringableObjects(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string {
                return '<script>alert("from Stringable")</script>';
            }
        };
        
        $var = new TemplateVariable($stringable);
        
        // Should escape stringable content
        $expected = '&lt;script&gt;alert(&quot;from Stringable&quot;)&lt;/script&gt;';
        $this->assertEquals($expected, (string) $var);
    }
    
    /**
     * Test invalid value type throws exception
     */
    public function testInvalidValueTypeThrowsException(): void
    {
        $invalidValue = new \stdClass();
        $var = new TemplateVariable($invalidValue);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be string, Stringable, TypedValue, or scalar');
        
        (string) $var;
    }
    
    /**
     * Test custom escaper
     */
    public function testCustomEscaper(): void
    {
        $customEscaper = new class implements \Dzentota\TemplateVariable\Escaper\EscaperInterface {
            public function escape(string $value, EscapeContext $context): string {
                return "CUSTOM: {$value}";
            }
            
            public function needsEscaping(string $value, EscapeContext $context): bool {
                return true;
            }
        };
        
        $var = new TemplateVariable('test', EscapeContext::HTML, $customEscaper);
        
        $this->assertEquals('CUSTOM: test', (string) $var);
    }
    
    /**
     * Test realistic usage scenarios
     */
    public function testRealisticUsageScenarios(): void
    {
        // Scenario 1: User input in HTML content
        $userName = new TemplateVariable('<script>alert(1)</script>');
        $html = "<p>Welcome, {$userName}!</p>";
        $this->assertStringNotContainsString('<script>', $html);
        
        // Scenario 2: User input in HTML attributes
        $userClass = new TemplateVariable('" onload="alert(1)" class="');
        $safeClass = $userClass('attr');
        $html = "<div class=\"user-input {$safeClass}\">Content</div>";
        // The dangerous pattern should be escaped, so quotes should be escaped
        $this->assertStringContainsString('&quot;', $html);
        $this->assertStringNotContainsString('" onload="alert(1)"', $html);
        
        // Scenario 3: Data in JavaScript
        $userData = new TemplateVariable('</script><script>alert(1)</script>');
        $safeData = $userData('js');
        $js = "var data = '{$safeData}';";
        $this->assertStringNotContainsString('</script>', $js);
        
        // Scenario 4: Safe numeric values
        $userId = new TemplateVariable(12345);
        $this->assertEquals('12345', (string) $userId);
        $this->assertFalse($userId->needsEscaping('html'));
    }
} 