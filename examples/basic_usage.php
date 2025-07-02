<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dzentota\TemplateVariable\TemplateVariable;
use Dzentota\TemplateVariable\Value\SimpleTypedValue;

echo "=== TemplateVariable Basic Usage Examples ===\n\n";

// Example 1: Basic automatic escaping (HTML context by default)
echo "1. Basic automatic escaping:\n";
$name = new TemplateVariable('<script>alert("xss")</script>User Name');
echo "Raw: " . $name->getRaw() . "\n";
echo "Auto-escaped: $name\n"; // Uses __toString() magic method
echo "Explicit HTML: " . $name('html') . "\n\n";

// Example 2: Attribute context escaping
echo "2. Attribute context escaping:\n";
$class = new TemplateVariable('class="malicious" onclick="alert(1)"');
echo "Raw: " . $class->getRaw() . "\n";
echo "For HTML: $class\n"; // Default HTML context
echo "For attribute: " . $class('attr') . "\n\n";

// Example 3: JavaScript context escaping  
echo "3. JavaScript context escaping:\n";
$jsData = new TemplateVariable('</script><script>alert("xss")</script>');
echo "Raw: " . $jsData->getRaw() . "\n";
echo "For JavaScript: " . $jsData('js') . "\n\n";

// Example 4: URL context escaping
echo "4. URL context escaping:\n";
$urlParam = new TemplateVariable('hello world & special chars!');
echo "Raw: " . $urlParam->getRaw() . "\n";
echo "For URL: " . $urlParam('url') . "\n\n";

// Example 5: CSS context escaping
echo "5. CSS context escaping:\n";
$cssValue = new TemplateVariable('color: red; }</style><script>alert(1)</script>');
echo "Raw: " . $cssValue->getRaw() . "\n";
echo "For CSS: " . $cssValue('css') . "\n\n";

// Example 6: Using with TypedValue objects
echo "6. Using with TypedValue objects:\n";
$typedValue = SimpleTypedValue::unsafe('<b>Bold Text</b>', 'html');
$templateVar = new TemplateVariable($typedValue);
echo "From TypedValue: $templateVar\n";

$safeTypedValue = SimpleTypedValue::safe('&lt;b&gt;Already Escaped&lt;/b&gt;', 'html');
$safeTemplateVar = new TemplateVariable($safeTypedValue);
echo "From safe TypedValue: $safeTemplateVar\n\n";

// Example 7: Using static factory methods
echo "7. Static factory methods:\n";
$htmlVar = TemplateVariable::html('<p>Paragraph</p>');
$attrVar = TemplateVariable::attr('value="test"');
$jsVar = TemplateVariable::js('var x = "data";');
$urlVar = TemplateVariable::url('hello world');
$rawVar = TemplateVariable::raw('<em>Not escaped</em>');

echo "HTML context: $htmlVar\n";
echo "Attr context: $attrVar\n";
echo "JS context: $jsVar\n";
echo "URL context: $urlVar\n";
echo "Raw context: $rawVar\n\n";

// Example 8: Template usage simulation
echo "8. Template usage simulation:\n";
$userName = new TemplateVariable('<script>alert("xss")</script>John');
$userClass = new TemplateVariable('user-card" onclick="alert(1)');
$userScript = new TemplateVariable('alert("xss")');

echo "<!-- Traditional PHP template with manual escaping -->\n";
echo "<span class=\"user\"><?=htmlspecialchars(\$userName);?></span>\n\n";

echo "<!-- With TemplateVariable - automatic escaping -->\n";
echo "<span class=\"user\">$userName</span>\n\n";

echo "<!-- With TemplateVariable - explicit context -->\n";
echo "<span class=\"" . $userClass('attr') . "\">$userName</span>\n";
echo "<script>var userData = " . $userScript('js') . ";</script>\n\n";

// Example 9: Checking if escaping is needed
echo "9. Checking if escaping is needed:\n";
$safeText = new TemplateVariable('Simple text');
$unsafeText = new TemplateVariable('<script>alert(1)</script>');

echo "Safe text needs HTML escaping: " . ($safeText->needsEscaping('html') ? 'yes' : 'no') . "\n";
echo "Unsafe text needs HTML escaping: " . ($unsafeText->needsEscaping('html') ? 'yes' : 'no') . "\n";

echo "\n=== End of Examples ===\n"; 