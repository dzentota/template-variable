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
        $var = TemplateVariable::create($maliciousInput);
        
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
        $var = TemplateVariable::create($maliciousInput);
        
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
        $var = TemplateVariable::create($userInput);
        
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
        $var = TemplateVariable::create($maliciousInput);
        
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
        $var = TemplateVariable::create($userInput);
        
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
        $var = TemplateVariable::create($userInput);
        
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
        $var = TemplateVariable::create($userInput);
        
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
     * Test getRaw method returns original value
     */
    public function testGetRaw(): void
    {
        $stringInput = '<script>alert("test")</script>';
        $stringVar = TemplateVariable::create($stringInput);
        $this->assertEquals($stringInput, $stringVar->getRaw());
        
        $intInput = 42;
        $intVar = TemplateVariable::create($intInput);
        $this->assertEquals($intInput, $intVar->getRaw());
        
        // Arrays now return TemplateVariableCollection
        $arrayInput = ['key' => 'value'];
        $arrayResult = TemplateVariable::create($arrayInput);
        $this->assertInstanceOf(\Dzentota\TemplateVariable\TemplateVariableCollection::class, $arrayResult);
        $this->assertEquals($arrayInput, $arrayResult->getRaw()); // getRaw() returns the original array
    }
    
    /**
     * Test create() method behavior
     */
    public function testCreateMethod(): void
    {
        // Test with string - should create TemplateVariable
        $stringVar = TemplateVariable::create('<script>alert(1)</script>');
        $this->assertInstanceOf(TemplateVariable::class, $stringVar);
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', (string) $stringVar);
        
        // Test with scalar - should create TemplateVariable
        $intVar = TemplateVariable::create(42);
        $this->assertInstanceOf(TemplateVariable::class, $intVar);
        $this->assertEquals('42', (string) $intVar);
        $this->assertFalse($intVar->needsEscaping('html'));
        
        // bool is returned as-is (since 1.2.0) so it keeps its truthiness in templates
        $boolVar = TemplateVariable::create(true);
        $this->assertTrue($boolVar);
        
        // Test with array - should return TemplateVariableCollection
        $arrayInput = ['test' => 'value'];
        $arrayResult = TemplateVariable::create($arrayInput);
        $this->assertInstanceOf(\Dzentota\TemplateVariable\TemplateVariableCollection::class, $arrayResult);
        $this->assertEquals($arrayInput, $arrayResult->getRaw());
        
        // Test with object - should return object as-is
        $objectInput = new \stdClass();
        $objectInput->prop = 'value';
        $objectResult = TemplateVariable::create($objectInput);
        $this->assertEquals($objectInput, $objectResult);
        $this->assertNotInstanceOf(TemplateVariable::class, $objectResult);
        
        // Test with Stringable - should create TemplateVariable
        $stringable = new class implements \Stringable {
            public function __toString(): string {
                return '<em>Stringable content</em>';
            }
        };
        $stringableVar = TemplateVariable::create($stringable);
        $this->assertInstanceOf(TemplateVariable::class, $stringableVar);
        $this->assertEquals('&lt;em&gt;Stringable content&lt;/em&gt;', (string) $stringableVar);
    }
    
    /**
     * Test needsEscaping method
     */
    public function testNeedsEscaping(): void
    {
        $safeInput = 'hello world';
        $dangerousInput = '<script>alert("XSS")</script>';
        
        $safeVar = TemplateVariable::create($safeInput);
        $dangerousVar = TemplateVariable::create($dangerousInput);
        
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
        $var = TemplateVariable::create('test');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown escape context: invalid');
        
        $var('invalid');
    }
    
    /**
     * Test TypedValue integration with mock object
     */
    public function testTypedValueIntegration(): void
    {
        // Create a mock TypedValue object that implements Typed interface
        $typedValue = new class implements \dzentota\TypedValue\Typed {
            public function toNative(): string {
                return '<script>alert("from TypedValue")</script>';
            }
            
            public static function fromNative($value): static {
                return new static();
            }
            
            public static function tryParse($value, ?\dzentota\TypedValue\Typed &$typed = null, ?\dzentota\TypedValue\ValidationResult &$result = null): bool {
                return true;
            }
            
            public function isNull(): bool {
                return false;
            }
            
            public function isSame(\dzentota\TypedValue\Typed $object): bool {
                return $this === $object;
            }
        };
        
        $var = TemplateVariable::create($typedValue);
        
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
        $boolVar = TemplateVariable::create(true);
        $this->assertEquals('1', (string) $boolVar);
        
        $boolFalseVar = TemplateVariable::create(false);
        $this->assertEquals('', (string) $boolFalseVar);
        
        $intVar = TemplateVariable::create(42);
        $this->assertEquals('42', (string) $intVar);
        
        $floatVar = TemplateVariable::create(3.14);
        $this->assertEquals('3.14', (string) $floatVar);
        
        $nullVar = TemplateVariable::create(null);
        $this->assertNull($nullVar); // null is not a scalar, so it's returned as-is
        
        // Scalar values should not need escaping
        $this->assertFalse($intVar->needsEscaping('html'));
        $this->assertFalse($floatVar->needsEscaping('html'));
        $this->assertFalse(TemplateVariable::html(true)->needsEscaping('html'));
    }
    
    /**
     * Test TypedValue with scalar result
     */
    public function testTypedValueWithScalarResult(): void
    {
        // TypedValue that returns a scalar
        $typedValue = new class implements \dzentota\TypedValue\Typed {
            public function toNative(): int {
                return 42;
            }
            
            public static function fromNative($value): static {
                return new static();
            }
            
            public static function tryParse($value, ?\dzentota\TypedValue\Typed &$typed = null, ?\dzentota\TypedValue\ValidationResult &$result = null): bool {
                return true;
            }
            
            public function isNull(): bool {
                return false;
            }
            
            public function isSame(\dzentota\TypedValue\Typed $object): bool {
                return $this === $object;
            }
        };
        
        $var = TemplateVariable::create($typedValue);
        
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
        
        $var = TemplateVariable::create($stringable);
        
        // Should escape stringable content
        $expected = '&lt;script&gt;alert(&quot;from Stringable&quot;)&lt;/script&gt;';
        $this->assertEquals($expected, (string) $var);
    }
    
    /**
     * Test non-escapable value type returns as-is 
     */
    public function testNonEscapableValueTypeReturnsAsIs(): void
    {
        $invalidValue = new \stdClass();
        $result = TemplateVariable::create($invalidValue);
        
        // Should return the object as-is, not a TemplateVariable
        $this->assertEquals($invalidValue, $result);
        $this->assertNotInstanceOf(TemplateVariable::class, $result);
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
        
        $var = TemplateVariable::create('test', EscapeContext::HTML, $customEscaper);
        
        $this->assertEquals('CUSTOM: test', (string) $var);
    }
    
    /**
     * Test realistic usage scenarios
     */
    public function testRealisticUsageScenarios(): void
    {
        // Scenario 1: User input in HTML content
        $userName = TemplateVariable::create('<script>alert(1)</script>');
        $html = "<p>Welcome, {$userName}!</p>";
        $this->assertStringNotContainsString('<script>', $html);
        
        // Scenario 2: User input in HTML attributes
        $userClass = TemplateVariable::create('" onload="alert(1)" class="');
        $safeClass = $userClass('attr');
        $html = "<div class=\"user-input {$safeClass}\">Content</div>";
        // The dangerous pattern should be escaped, so quotes should be escaped
        $this->assertStringContainsString('&quot;', $html);
        $this->assertStringNotContainsString('" onload="alert(1)"', $html);
        
        // Scenario 3: Data in JavaScript
        $userData = TemplateVariable::create('</script><script>alert(1)</script>');
        $safeData = $userData('js');
        $js = "var data = '{$safeData}';";
        $this->assertStringNotContainsString('</script>', $js);
        
        // Scenario 4: Safe numeric values
        $userId = TemplateVariable::create(12345);
        $this->assertEquals('12345', (string) $userId);
        $this->assertFalse($userId->needsEscaping('html'));
    }
} 