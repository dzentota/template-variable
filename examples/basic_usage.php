<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dzentota\TemplateVariable\TemplateVariable;

echo "=== TemplateVariable Library Examples ===\n\n";

// Example 1: Basic string escaping
echo "1. Basic String Escaping:\n";
$userInput = '<script>alert("XSS Attack!")</script>';
$var = TemplateVariable::create($userInput);

echo "Original: " . $userInput . "\n";
echo "Auto-escaped: " . $var . "\n";
echo "Explicit HTML: " . $var('html') . "\n\n";

// Example 2: Attribute injection protection
echo "2. Attribute Injection Protection:\n";
$maliciousClass = '" onload="alert(1)" class="';
$classVar = TemplateVariable::create($maliciousClass);

echo "Original: " . $maliciousClass . "\n";
echo "Safe for attributes: " . $classVar('attr') . "\n";
echo "HTML: <div class=\"user-input " . $classVar('attr') . "\">Content</div>\n\n";

// Example 3: JavaScript context
echo "3. JavaScript Context Escaping:\n";
$jsData = '</script><script>alert("XSS")</script>';
$jsVar = TemplateVariable::create($jsData);

echo "Original: " . $jsData . "\n";
echo "Safe for JS: " . $jsVar('js') . "\n";
echo "JavaScript: var data = '" . $jsVar('js') . "';\n\n";

// Example 4: Multiple contexts
echo "4. Multiple Context Examples:\n";
$data = 'Hello "World" & <Friends>';
$multiVar = TemplateVariable::create($data);

echo "Original: " . $data . "\n";
echo "HTML: " . $multiVar('html') . "\n";
echo "Attribute: " . $multiVar('attr') . "\n";
echo "JavaScript: " . $multiVar('js') . "\n";
echo "URL: " . $multiVar('url') . "\n";
echo "CSS: " . $multiVar('css') . "\n";
echo "Raw: " . $multiVar('raw') . "\n\n";

// Example 5: Scalar values (not tainted)
echo "5. Scalar Values (Not Tainted):\n";
$boolVar = TemplateVariable::create(true);
$intVar = TemplateVariable::create(42);
$floatVar = TemplateVariable::create(3.14159);
$nullVar = TemplateVariable::create(null);

echo "Boolean true: " . $boolVar . " (needs escaping: " . ($boolVar->needsEscaping('html') ? 'yes' : 'no') . ")\n";
echo "Integer: " . $intVar . " (needs escaping: " . ($intVar->needsEscaping('html') ? 'yes' : 'no') . ")\n";
echo "Float: " . $floatVar . " (needs escaping: " . ($floatVar->needsEscaping('html') ? 'yes' : 'no') . ")\n";
echo "Null: '" . ($nullVar ?? 'null') . "' (returned as-is, not wrapped)\n\n";

// Example 6: TypedValue integration vs regular objects
echo "6. TypedValue Integration vs Regular Objects:\n";

// Proper TypedValue that implements dzentota\TypedValue\Typed interface
$realTypedValue = new class implements \dzentota\TypedValue\Typed {
    public function toNative(): string
    {
        return '<script>alert("From Real TypedValue")</script>';
    }
    
    public static function fromNative($value): static
    {
        return new static();
    }
    
    public static function tryParse($value, ?\dzentota\TypedValue\Typed &$typed = null, ?\dzentota\TypedValue\ValidationResult &$result = null): bool
    {
        return true;
    }
    
    public function isNull(): bool
    {
        return false;
    }
    
    public function isSame(\dzentota\TypedValue\Typed $object): bool
    {
        return $this === $object;
    }
};

// Regular object with toNative() method (NOT implementing Typed interface)
$regularObject = new class {
    public function toNative(): string
    {
        return '<script>alert("From Regular Object")</script>';
    }
};

$realTv = TemplateVariable::create($realTypedValue);
$regularObj = TemplateVariable::create($regularObject);

echo "Real TypedValue: " . $realTv . " (extracted via toNative() and escaped)\n";
echo "Regular object: " . get_class($regularObj) . " (returned as-is, toNative() ignored)\n\n";

// Example 6.5: Collection handling demonstration
echo "6.5. Collection Handling - Secure by Default:\n";

// Create a collection of potentially dangerous data
$dangerousData = [
    '<script>alert("XSS in item 1")</script>',
    '" onload="alert(\'XSS in item 2\')" class="',
    'Safe item 3',
    42,
    true
];

$secureCollection = TemplateVariable::create($dangerousData);
echo "Collection type: " . get_class($secureCollection) . "\n";
echo "Collection count: " . count($secureCollection) . "\n";

echo "Secure iteration:\n";
foreach ($secureCollection as $index => $item) {
    $itemType = is_object($item) ? get_class($item) : gettype($item);
    $output = $item === null ? 'null' : (string) $item;
    echo "  [{$index}] {$itemType}: {$output}\n";
}

echo "Array-style access:\n";
echo "  First item: " . $secureCollection[0] . "\n";
echo "  Second item: " . $secureCollection[1] . "\n\n";

// Example 7: Stringable objects
echo "7. Stringable Objects:\n";
$stringable = new class implements Stringable {
    public function __toString(): string
    {
        return '<em>Stringable content</em>';
    }
};

$stringableVar = TemplateVariable::create($stringable);
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
$userName = TemplateVariable::create('<script>alert("XSS")</script>');
$userId = TemplateVariable::create(12345);
$userClass = TemplateVariable::create('" onload="alert(1)" class="');
$userComment = TemplateVariable::create('Check out this link: </script><script>alert(1)</script>');

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
$safeVar = TemplateVariable::create($dangerousInput);
echo "   <div>" . $safeVar . "</div>\n";

echo "\n=== Examples Complete ===\n"; 