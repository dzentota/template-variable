# TemplateVariable - Secure Context-Aware Template Variables for PHP

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)

A secure template variable library that provides context-aware escaping for PHP templates, supporting the [AppSec Manifesto](https://github.com/dzentota/AppSecManifesto) principles by automatically preventing XSS vulnerabilities through intelligent output escaping.

## 🚀 Features

- **🔒 Automatic Context-Aware Escaping** - Default HTML escaping via `__toString()` magic method
- **🎯 Explicit Context Control** - Specify exact context via `__invoke()` method  
- **📚 Collection-Aware Security** - Arrays and Traversable objects wrapped for secure iteration
- **🏷️ TypedValue Integration** - Works with [dzentota/typedvalue](https://github.com/dzentota/typedvalue) objects
- **⚡ Scalar Value Safety** - Treats scalar values (int, float, bool, null) as safe (not tainted)
- **🌐 Multiple Context Support** - HTML, attributes, JavaScript, CSS, URL, and raw contexts
- **🔧 Extensible Design** - Pluggable escaper interface for custom implementations
- **🧪 Fully Tested** - Comprehensive test suite with realistic attack vectors

## Why TemplateVariable?

Most template engines provide security through escaping, but they require developers to remember to escape values or use specific syntax. TemplateVariable brings this security directly into your PHP variables using magic methods, making secure templates the default behavior.

### The Problem

Traditional PHP templates require manual escaping:

```php
<span><?= htmlspecialchars($name); ?></span>
<span class="<?= htmlspecialchars($class, ENT_QUOTES); ?>"><?= htmlspecialchars($name); ?></span>
<script>var data = <?= json_encode($jsData); ?>;</script>
```

### The TemplateVariable Solution

With TemplateVariable, escaping happens automatically:

```php
<span><?= $name; ?></span>
<span class="<?= $class('attr'); ?>"><?= $name; ?></span>
<script>var data = <?= $jsData('js'); ?>;</script>
```

## 📦 Installation

```bash
composer require dzentota/template-variable
```

## 🎯 Core Concept

Transform this vulnerable pattern:
```php
<!-- ❌ VULNERABLE -->
<span><?= $userInput; ?></span>
<div class="<?= $className; ?>">Content</div>
<script>var data = '<?= $jsonData; ?>';</script>
```

Into this secure pattern:
```php
<!-- ✅ SECURE -->
<span><?= $userInput; ?></span>                    <!-- Auto-escaped for HTML -->
<div class="<?= $className('attr'); ?>">Content</div>    <!-- Explicit attribute context -->
<script>var data = '<?= $jsonData('js'); ?>';</script>    <!-- Explicit JavaScript context -->
```

## 💡 Value Type Support

TemplateVariable intelligently handles different value types:

- **Strings & Stringable** - Escaped according to context
- **Arrays & Traversable** - Wrapped in `TemplateVariableCollection` for secure iteration
- **TypedValue objects** - Value extracted with `toNative()`, then processed
- **Scalar values** (bool, float, int, null) - Returned as-is (not tainted)
- **Custom objects** - Must implement `Stringable` or have `toNative()` method

## 📚 Collection Security (New!)

TemplateVariable now provides **secure-by-default** handling for collections, addressing a critical gap in template security:

### The Problem
Traditional template security fails with collections:
```php
// ❌ VULNERABLE - Each user object bypasses escaping
$users = $arrayOfUserObjects;
foreach ($users as $user) {
    echo "<span>{$user->name}</span>"; // XSS vulnerability!
}
```

### The Solution
TemplateVariable collections automatically wrap each item:
```php
// ✅ SECURE - Each item automatically wrapped
$users = TemplateVariable::create($arrayOfUserObjects);
foreach ($users as $user) {
    echo "<span>{$user}</span>"; // Automatically escaped!
}

// Array-style access also secure
echo $users[0]; // First user, automatically escaped
```

### Collection Features
- **Automatic Item Wrapping** - Each accessed item becomes a `TemplateVariable`
- **Nested Collection Support** - Arrays of arrays handled recursively
- **Traversable Support** - Works with any `Traversable` object
- **Context Propagation** - Default escape context passed to all items
- **Read-Only Access** - Collections cannot be modified (security by design)

## 📚 Quick Start

### Basic Usage

```php
use Dzentota\TemplateVariable\TemplateVariable;

// Automatic HTML escaping
$name = TemplateVariable::create('<script>alert("XSS")</script>');
echo $name; // &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;

// Explicit context escaping  
$attr = TemplateVariable::create('" onload="alert(1)" class="');
echo $attr('attr'); // &quot; onload=&quot;alert(1)&quot; class=&quot;

// Multiple contexts
$data = TemplateVariable::create('</script><script>alert(1)</script>');
echo $data('html'); // &lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt;
echo $data('js');   // \u003C\/script\u003E\u003Cscript\u003Ealert(1)\u003C\/script\u003E
echo $data('url');  // %3C%2Fscript%3E%3Cscript%3Ealert%281%29%3C%2Fscript%3E
```

### TypedValue Integration

```php
// Works with dzentota/typedvalue objects that implement Typed interface
use Some\TypedValue\Email;

$email = Email::fromNative('user@example.com');
$template = TemplateVariable::create($email);
echo $template; // user@example.com (extracted via toNative())

// IMPORTANT: Only objects implementing dzentota\TypedValue\Typed interface 
// will have toNative() called. Regular objects are returned as-is.

// Real TypedValue returning malicious content gets escaped
$maliciousTyped = new class implements \dzentota\TypedValue\Typed {
    public function toNative(): string {
        return '<script>alert("XSS")</script>';
    }
    // ... other required interface methods
};
$var = TemplateVariable::create($maliciousTyped);
echo $var; // &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;

// Regular object with toNative() method - toNative() is IGNORED
$regularObject = new class {
    public function toNative(): string {
        return '<script>alert("XSS")</script>';
    }
};
$result = TemplateVariable::create($regularObject);
// $result is the original object, NOT a TemplateVariable
```

### Collection Security in Action

```php
// Simulate ActiveRecord-style objects with potential XSS
$users = [
    (object)['name' => '<script>alert("Admin")</script>', 'role' => 'admin'],
    (object)['name' => '" onload="alert(1)" class="', 'role' => 'user'],
    (object)['name' => 'Safe User', 'role' => 'user']
];

// Secure collection wrapping
$safeUsers = TemplateVariable::create($users);

foreach ($safeUsers as $user) {
    // Each $user is automatically wrapped as TemplateVariable
    echo "<div class=\"user\">{$user}</div>"; // Safe output!
}

// Nested arrays also secured
$userGroups = [
    'admins' => [
        (object)['name' => '<script>alert("XSS")</script>'],
        (object)['name' => 'Safe Admin']
    ],
    'users' => [
        (object)['name' => '" onclick="alert(1)"'],
    ]
];

$safeGroups = TemplateVariable::create($userGroups);
foreach ($safeGroups as $groupName => $group) {
    echo "<h3>{$groupName}</h3>";
    foreach ($group as $user) {
        echo "<p>{$user}</p>"; // Nested security!
    }
}
```

### Scalar Value Safety

```php
// Scalar values are treated as safe (not tainted)
$userId = TemplateVariable::create(12345);
$isActive = TemplateVariable::create(true);
$price = TemplateVariable::create(99.99);

echo $userId;   // 12345
echo $isActive; // 1
echo $price;    // 99.99

// No escaping needed for scalars
var_dump($userId->needsEscaping('html')); // false
```

## 🎨 Context Support

### HTML Context (Default)
```php
$var = TemplateVariable::create('<b>Bold</b>');
echo $var;          // &lt;b&gt;Bold&lt;/b&gt;
echo $var('html');  // &lt;b&gt;Bold&lt;/b&gt;
```

### Attribute Context
```php
$class = TemplateVariable::create('" onclick="alert(1)" class="');
echo "<div class=\"{$class('attr')}\">"; // Safe attribute injection prevention
```

### JavaScript Context
```php
$data = TemplateVariable::create('</script><script>alert(1)</script>');
echo "var x = '{$data('js')}';"; // Flexible escaping without quotes
```

### CSS Context
```php
$style = TemplateVariable::create('color: red; </style><script>alert(1)</script>');
echo "body { content: '{$style('css')}'; }";
```

### URL Context
```php
$param = TemplateVariable::create('hello world & more');
echo "https://example.com?q={$param('url')}";
```

### Raw Context (No Escaping)
```php
$trusted = TemplateVariable::create('<em>Trusted HTML</em>');
echo $trusted('raw'); // <em>Trusted HTML</em> - no escaping
```

## 🏭 Static Factory Methods

```php
// Context-specific factory methods
$html = TemplateVariable::html('<script>');
$attr = TemplateVariable::attr('" onclick="');
$js = TemplateVariable::js('</script>');
$css = TemplateVariable::css('</style>');
$url = TemplateVariable::url('hello world');
$raw = TemplateVariable::raw('<b>trusted</b>');
```

## 🛡️ Security Examples

### Realistic Attack Vectors

```php
// Attribute injection attack
$userClass = TemplateVariable::create('" onload="alert(1)" class="');
$html = "<div class=\"user-content {$userClass('attr')}\">Safe</div>";
// Result: <div class="user-content &quot; onload=&quot;alert(1)&quot; class=&quot;">Safe</div>

// JavaScript context attack
$userData = TemplateVariable::create('"; alert("XSS"); var x="');
$js = "var message = '{$userData('js')}';";
// Result: var message = '\"; alert(\"XSS\"); var x=\"';

// Multi-context template
$name = TemplateVariable::create('<script>alert("XSS")</script>');
$id = TemplateVariable::create(42);
$comment = TemplateVariable::create('Check out </script><script>alert(1)</script>');

$template = <<<HTML
<div class="user" data-id="{$id}">
    <h1>Welcome, {$name}!</h1>
    <p>{$comment}</p>
</div>
<script>
    var user = {
        name: '{$name('js')}',
        id: {$id},
        comment: '{$comment('js')}'
    };
</script>
HTML;
```

## 🔧 Advanced Configuration

### Custom Escaper

```php
use Dzentota\TemplateVariable\Escaper\EscaperInterface;
use Dzentota\TemplateVariable\Context\EscapeContext;

class CustomEscaper implements EscaperInterface 
{
    public function escape(string $value, EscapeContext $context): string 
    {
        // Custom escaping logic
        return "SAFE: " . htmlspecialchars($value);
    }
    
    public function needsEscaping(string $value, EscapeContext $context): bool 
    {
        return str_contains($value, '<');
    }
}

$var = TemplateVariable::create('<test>', EscapeContext::HTML, new CustomEscaper());
echo $var; // SAFE: &lt;test&gt;
```

### Default Context Override

```php
// Set JavaScript as default context instead of HTML
$var = TemplateVariable::create('<script>', EscapeContext::JAVASCRIPT);
echo $var; // \u003Cscript\u003E (JavaScript escaping)
```

## 🧪 Utility Methods

```php
$var = TemplateVariable::create('<script>alert(1)</script>');

// Check if escaping is needed
$var->needsEscaping('html');     // true
$var->needsEscaping('raw');      // false

// Get raw value
$var->getRaw();                  // <script>alert(1)</script>

// Context shorthand
$var('h');    // HTML context
$var('a');    // Attribute context  
$var('j');    // JavaScript context
$var('c');    // CSS context
$var('u');    // URL context
$var('r');    // Raw context
```

## 🎯 Before/After Comparison

### Traditional Approach
```php
<!-- Manual escaping - error prone -->
<span><?= htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8'); ?></span>
<div class="<?= htmlspecialchars($className, ENT_QUOTES, 'UTF-8'); ?>">
<script>var data = <?= json_encode($jsonData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;</script>

<!-- Easy to forget or make mistakes -->
<span><?= $userInput; ?></span> <!-- ❌ VULNERABLE -->
```

### TemplateVariable Approach
```php
<!-- Automatic and context-aware -->
<span><?= $userInput; ?></span>                    <!-- ✅ Auto-escaped -->
<div class="<?= $className('attr'); ?>">           <!-- ✅ Attribute-safe -->
<script>var data = '<?= $jsonData('js'); ?>';</script>    <!-- ✅ JS-safe -->

<!-- Secure by default, explicit when needed -->
```

## 🏗️ Architecture

### Core Components

- **TemplateVariable** - Main class with magic methods for individual values
- **TemplateVariableCollection** - Collection wrapper that secures arrays and Traversable objects
- **ContextAwareEscaper** - Multi-context escaping implementation  
- **EscapeContext** - Enum defining available contexts
- **EscaperInterface** - Contract for custom escapers

### Integration Points

- **dzentota/typedvalue** - Automatic integration via `toNative()` method
- **Stringable** - Support for objects implementing `__toString()`
- **Custom Escapers** - Pluggable escaping strategies

## 📋 Requirements

- **PHP 8.1+** (uses enums and readonly properties)
- **mbstring extension** (for JavaScript escaping)

## 🧪 Testing

```bash
# Run tests
./vendor/bin/phpunit

# Run examples
php examples/basic_usage.php
```

## 🔗 Related Projects

- **[dzentota/typedvalue](https://github.com/dzentota/typedvalue)** - Domain primitive value objects
- **[AppSec Manifesto](https://github.com/dzentota/AppSecManifesto)** - Security-first development principles

## 📄 License

MIT License. See [LICENSE](LICENSE) file for details.

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 🛡️ Security

This library is designed to prevent XSS attacks through automatic context-aware escaping. However, security is a shared responsibility:

- **Always use explicit contexts** for non-HTML output
- **Validate input** at application boundaries  
- **Use TypedValue objects** for structured data
- **Keep the library updated** to latest version
- **Test your templates** with malicious input

For security issues, please email [webtota@gmail.com](mailto:webtota@gmail.com).

---

**Making secure templating the default choice.** 🔒

## AppSec Manifesto Compliance

This library implements several principles from the [AppSec Manifesto](https://github.com/dzentota/AppSecManifesto):

### Rule #4: The Vigilant Validator (Input Validation and Output Encoding)

- **Context-Aware Output Encoding**: Automatically applies appropriate escaping based on output context
- **Prevent XSS**: Eliminates XSS vulnerabilities through systematic output encoding
- **Secure by Default**: Makes secure escaping the default behavior, not an opt-in feature

### Rule #0: Absolute Zero (Minimizing Attack Surface)

- **Least Expressive Language**: Uses the least expressive escaping needed for each context
- **Input Minimization**: Reduces the complexity of manual escaping decisions

## Advanced Usage

### Custom Escaper

Implement your own escaping logic:

```php
use Dzentota\TemplateVariable\Escaper\EscaperInterface;

class CustomEscaper implements EscaperInterface 
{
    public function escape(string $value, EscapeContext $context): string 
    {
        // Your custom escaping logic
    }
    
    public function needsEscaping(string $value, EscapeContext $context): bool 
    {
        // Your custom validation logic
    }
}

$var = TemplateVariable::create('data', EscapeContext::HTML, new CustomEscaper());
```

### Checking if Escaping is Needed

```php
$var = TemplateVariable::create('simple text');
if ($var->needsEscaping('html')) {
    echo "This value needs HTML escaping";
}
```

## Security Features

- ✅ **Automatic XSS Prevention**: Default HTML escaping prevents most XSS attacks
- ✅ **Context-Aware Escaping**: Different escaping for HTML, attributes, JavaScript, CSS, and URLs
- ✅ **TypedValue Integration**: Works with strongly-typed value objects
- ✅ **Secure by Default**: Escaping is applied automatically, not opt-in
- ✅ **No Performance Overhead**: Escaping only applied when needed
- ✅ **Developer Friendly**: Simple API with magic methods for seamless integration

## Requirements

- PHP 8.1 or higher
- No additional dependencies for core functionality

## Contributing

Contributions are welcome! Please read our contributing guidelines and submit pull requests to help improve the library.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Related Projects

- [AppSec Manifesto](https://github.com/dzentota/AppSecManifesto) - Comprehensive application security principles
- [TypedValue](https://github.com/dzentota/typedvalue) - Strongly-typed value objects for PHP

## Author

**Alex Tatulchenkov** - AppSec Leader | Enterprise Web Defender

- LinkedIn: [View Profile](https://www.linkedin.com/in/alex-tatulchenkov/)
- GitHub: [@dzentota](https://github.com/dzentota) 