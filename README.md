# TemplateVariable - Secure Context-Aware Template Variables for PHP

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)

A secure template variable library that provides context-aware escaping for PHP templates, supporting the [AppSec Manifesto](https://github.com/dzentota/AppSecManifesto) principles by automatically preventing XSS vulnerabilities through intelligent output escaping.

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

## Installation

```bash
composer require dzentota/template-variable
```

## Basic Usage

### Automatic HTML Escaping

```php
use Dzentota\TemplateVariable\TemplateVariable;

$name = new TemplateVariable('<script>alert("xss")</script>John');
echo $name; // Output: &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;John
```

### Context-Specific Escaping

```php
$class = new TemplateVariable('user-card" onclick="alert(1)');
$jsData = new TemplateVariable('</script><script>alert("xss")</script>');
$url = new TemplateVariable('hello world & special chars');

// Different contexts require different escaping
echo $class('attr');  // For HTML attributes
echo $jsData('js');   // For JavaScript context  
echo $url('url');     // For URL parameters
```

## Magic Methods

TemplateVariable uses PHP's magic methods to provide seamless integration:

### `__toString()` - Automatic Escaping

When used in a string context, automatically applies HTML escaping:

```php
$var = new TemplateVariable('<b>Bold</b>');
echo "Hello $var"; // Automatically escaped
echo $var;         // Automatically escaped
```

### `__invoke()` - Explicit Context

Explicitly specify the escaping context:

```php
$var = new TemplateVariable('data & more data');

echo $var('html');  // HTML context
echo $var('attr');  // HTML attribute context
echo $var('js');    // JavaScript context
echo $var('url');   // URL context
echo $var('css');   // CSS context
echo $var('raw');   // No escaping
```

## Supported Contexts

| Context | Method | Usage | Escaping Applied |
|---------|--------|--------|------------------|
| HTML | `$var('html')` or `$var` | HTML content | `htmlspecialchars()` |
| Attribute | `$var('attr')` | HTML attributes | `htmlspecialchars()` with quotes |
| JavaScript | `$var('js')` | JavaScript code | `json_encode()` with flags |
| CSS | `$var('css')` | CSS values | CSS-specific escaping |
| URL | `$var('url')` | URL parameters | `rawurlencode()` |
| Raw | `$var('raw')` | No escaping needed | No escaping |

## TypedValue Integration

TemplateVariable is designed to work with TypedValue objects from the [dzentota/typedvalue](https://github.com/dzentota/typedvalue) library:

```php
use Dzentota\TemplateVariable\Value\SimpleTypedValue;

$typedValue = SimpleTypedValue::unsafe('<script>alert("xss")</script>', 'string');
$templateVar = new TemplateVariable($typedValue);
echo $templateVar; // Properly escaped
```

## Static Factory Methods

For convenience, use static factory methods:

```php
$htmlVar = TemplateVariable::html('<p>Content</p>');
$attrVar = TemplateVariable::attr('class-name');
$jsVar = TemplateVariable::js('user input');
$urlVar = TemplateVariable::url('query param');
$rawVar = TemplateVariable::raw('<b>trusted content</b>');
```

## Template Usage Examples

### Before (Manual Escaping)

```php
<div class="<?= htmlspecialchars($userClass, ENT_QUOTES); ?>">
    <h1><?= htmlspecialchars($title); ?></h1>
    <p><?= htmlspecialchars($description); ?></p>
    <script>
        var userData = <?= json_encode($jsData); ?>;
    </script>
    <a href="?search=<?= rawurlencode($searchTerm); ?>">Search</a>
</div>
```

### After (TemplateVariable)

```php
<div class="<?= $userClass('attr'); ?>">
    <h1><?= $title; ?></h1>
    <p><?= $description; ?></p>
    <script>
        var userData = <?= $jsData('js'); ?>;
    </script>
    <a href="?search=<?= $searchTerm('url'); ?>">Search</a>
</div>
```

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

$var = new TemplateVariable('data', EscapeContext::HTML, new CustomEscaper());
```

### Checking if Escaping is Needed

```php
$var = new TemplateVariable('simple text');
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

- LinkedIn: [View Profile](https://linkedin.com/in/dzentota)
- GitHub: [@dzentota](https://github.com/dzentota) 