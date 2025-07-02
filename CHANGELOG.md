# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-30

### Added

#### Core Features
- **TemplateVariable Class**: Main class implementing `__toString()` and `__invoke()` magic methods for context-aware escaping
- **Context-Aware Escaping**: Support for HTML, HTML attributes, JavaScript, CSS, URL, and raw contexts
- **TypedValue Integration**: Interface and implementation for working with typed value objects
- **Automatic XSS Prevention**: Default HTML escaping prevents most XSS attacks

#### Escaping Contexts
- `html` - HTML content escaping using `htmlspecialchars()`
- `attr` - HTML attribute escaping with quotes protection
- `js` - JavaScript context escaping using `json_encode()` with security flags
- `css` - CSS value escaping (basic implementation)
- `url` - URL parameter escaping using `rawurlencode()`
- `raw` - No escaping for trusted content

#### Magic Methods
- `__toString()` - Automatic HTML escaping when used in string context
- `__invoke()` - Explicit context specification: `$var('html')`, `$var('attr')`, etc.

#### Static Factory Methods
- `TemplateVariable::html()` - Create with HTML context
- `TemplateVariable::attr()` - Create with attribute context
- `TemplateVariable::js()` - Create with JavaScript context
- `TemplateVariable::css()` - Create with CSS context
- `TemplateVariable::url()` - Create with URL context
- `TemplateVariable::raw()` - Create with raw (no escaping) context

#### Security Features
- **Secure by Default**: Escaping is applied automatically, not opt-in
- **AppSec Manifesto Compliance**: Implements principles from the AppSec Manifesto
- **Performance Optimized**: Escaping only applied when needed
- **TypedValue Safety**: Respects pre-escaped/validated values

#### Developer Experience
- **Simple API**: Easy-to-use magic methods for seamless integration
- **Comprehensive Documentation**: Detailed README with examples
- **Full Test Coverage**: PHPUnit tests for all functionality
- **Example Code**: Working examples demonstrating all features

#### Integration
- **Composer Package**: Ready for installation via Composer
- **PSR-4 Autoloading**: Modern PHP namespace structure
- **PHP 8.1+ Support**: Uses modern PHP features like enums and match expressions
- **No Dependencies**: Core functionality requires no external libraries

### Security
- Prevents XSS vulnerabilities through systematic output encoding
- Context-aware escaping reduces attack surface
- Safe handling of TypedValue objects
- Comprehensive escaping for all major output contexts

### Documentation
- Comprehensive README with usage examples
- AppSec Manifesto compliance documentation
- Template usage examples (before/after comparisons)
- Advanced usage examples with custom escapers
- Security features documentation

[1.0.0]: https://github.com/dzentota/template-variable/releases/tag/v1.0.0 