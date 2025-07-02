<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dzentota\TemplateVariable\TemplateVariable;
use Dzentota\TemplateVariable\Context\EscapeContext;

echo "=== TemplateVariable Library Examples ===\n\n";

// Example 1: Basic string escaping
echo "1. Basic String Escaping:\n";
$userInput = '<script>alert("XSS Attack!")</script>';
$var = new TemplateVariable($userInput);

echo "Original: " . $userInput . "\n";
echo "Auto-escaped: " . $var . "\n";
echo "Explicit HTML: " . $var('html') . "\n\n";

// Example 2: Attribute injection protection
echo "2. Attribute Injection Protection:\n";
$maliciousClass = '" onload="alert(1)" class="';
$classVar = new TemplateVariable($maliciousClass);

echo "Original: " . $maliciousClass . "\n";
echo "Safe for attributes: " . $classVar('attr') . "\n";
echo "HTML: <div class=\"user-input " . $classVar('attr') . "\">Content</div>\n\n";

// Example 3: JavaScript context
echo "3. JavaScript Context Escaping:\n";
$jsData = '</script><script>alert("XSS")</script>';
$jsVar = new TemplateVariable($jsData);

echo "Original: " . $jsData . "\n";
echo "Safe for JS: " . $jsVar('js') . "\n";
echo "JavaScript: var data = '" . $jsVar('js') . "';\n\n";

// Example 4: Multiple contexts
echo "4. Multiple Context Examples:\n";
$data = 'Hello "World" & <Friends>';
$multiVar = new TemplateVariable($data);

echo "Original: " . $data . "\n";
echo "HTML: " . $multiVar('html') . "\n";
echo "Attribute: " . $multiVar('attr') . "\n";
echo "JavaScript: " . $multiVar('js') . "\n";
echo "URL: " . $multiVar('url') . "\n";
echo "CSS: " . $multiVar('css') . "\n";
echo "Raw: " . $multiVar('raw') . "\n\n";

// Example 5: Scalar values (not tainted)
echo "5. Scalar Values (Not Tainted):\n";
$boolVar = new TemplateVariable(true);
$intVar = new TemplateVariable(42);
$floatVar = new TemplateVariable(3.14159);
$nullVar = new TemplateVariable(null);

echo "Boolean true: " . $boolVar . " (needs escaping: " . ($boolVar->needsEscaping('html') ? 'yes' : 'no') . ")\n";
echo "Integer: " . $intVar . " (needs escaping: " . ($intVar->needsEscaping('html') ? 'yes' : 'no') . ")\n";
echo "Float: " . $floatVar . " (needs escaping: " . ($floatVar->needsEscaping('html') ? 'yes' : 'no') . ")\n";
echo "Null: '" . $nullVar . "' (needs escaping: " . ($nullVar->needsEscaping('html') ? 'yes' : 'no') . ")\n\n";

// Example 6: TypedValue integration
echo "6. TypedValue Integration:\n";

// Mock TypedValue object that returns a string
$typedValueString = new class {
    public function toNative(): string {
        return '<script>alert("From TypedValue")</script>';
    }
};

// Mock TypedValue object that returns a scalar
$typedValueInt = new class {
    public function toNative(): int {
        return 12345;
    }
};

$tvString = new TemplateVariable($typedValueString);
$tvInt = new TemplateVariable($typedValueInt);

echo "TypedValue (string): " . $tvString . "\n";
echo "TypedValue (int): " . $tvInt . " (needs escaping: " . ($tvInt->needsEscaping('html') ? 'yes' : 'no') . ")\n\n";

// Example 7: Stringable objects
echo "7. Stringable Objects:\n";
$stringable = new class implements Stringable {
    public function __toString(): string {
        return '<em>Stringable content</em>';
    }
};

$stringableVar = new TemplateVariable($stringable);
echo "Stringable: " . $stringableVar . "\n\n";

// Example 8: Static factory methods
echo "8. Static Factory Methods:\n";
$content = '<b>Bold Text</b>';

echo "HTML factory: " . TemplateVariable::html($content) . "\n";
echo "Attr factory: " . TemplateVariable::attr($content) . "\n";
echo "JS factory: " . TemplateVariable::js($content) . "\n";
echo "URL factory: " . TemplateVariable::url($content) . "\n";
echo "Raw factory: " . TemplateVariable::raw($content) . "\n\n";

// Example 9: Realistic template usage
echo "9. Realistic Template Usage:\n";

// Simulate template variables
$userName = new TemplateVariable('<script>alert("XSS")</script>');
$userId = new TemplateVariable(12345);
$userClass = new TemplateVariable('" onload="alert(1)" class="');
$userComment = new TemplateVariable('Check out this link: </script><script>alert(1)</script>');

// Template with automatic escaping
$template = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
</head>
<body>
    <div class="user-profile {$userClass('attr')}">
        <h1>Welcome, {$userName}!</h1>
        <p>User ID: {$userId}</p>
        <div class="comment">{$userComment}</div>
    </div>
    
    <script>
        var user = {
            name: '{$userName('js')}',
            id: {$userId('js')},
            comment: '{$userComment('js')}'
        };
        console.log('User data:', user);
    </script>
</body>
</html>
HTML;

echo $template . "\n\n";

// Example 10: Before and after comparison
echo "10. Before/After Comparison:\n";
$dangerousInput = '<img src="x" onerror="alert(\'XSS\')">';

echo "❌ BEFORE (vulnerable):\n";
echo "   <div>" . $dangerousInput . "</div>\n";

echo "✅ AFTER (secure):\n";
$safeVar = new TemplateVariable($dangerousInput);
echo "   <div>" . $safeVar . "</div>\n";

echo "\n=== Examples Complete ===\n"; 