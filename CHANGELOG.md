# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - Unreleased

### Added
- `EscapeContext::URL_ATTR` (`'url_attr'`; aliases `'href'`, `'src'`, `'urlattr'`) for whole URLs in
  HTML attributes: trims, rejects control/whitespace/invisible characters, allowlists schemes
  (http, https, mailto, relative refs), checks entity- and percent-decoded forms, rejects
  protocol-relative `//host` by default, replaces unsafe values with `#`, then attribute-escapes.
- `ContextAwareEscaper` constructor options: `allowedUrlSchemes`, `allowProtocolRelative`, `unsafeUrlPlaceholder`;
  public `sanitizeUrl()`.
- `TemplateVariable::urlAttr()` factory.
- `TemplateVariable::isTruthy()` and `TemplateVariableCollection::isTruthy()`.
- `'url_component'` alias for the existing `url` context.

### Changed
- **BC break:** `TemplateVariable::create()` (and collection iteration/array access) now returns `bool`
  values as-is instead of wrapping them, so `if ($flag)` in templates is no longer always true.
  Echo output is unchanged (`true` → `1`, `false` → ``); calling `$flag('html')` on a bool no longer works.
  Use `TemplateVariable::html($bool)` if a wrapped bool is really required.
- The `url` context is documented as "URL component" encoding; its behaviour is unchanged.
- Requires PHP >= 8.3.

### Fixed
- `TemplateVariableCollection::offsetGet()` no longer throws for keys whose value is `null`.

## [1.1.0] - 2025-07-03

### Added
- **TemplateVariableCollection** - Major new feature for collection security
- Automatic wrapping of arrays and Traversable objects for secure iteration
- Collection items are automatically wrapped as TemplateVariable instances
- Support for nested collections with recursive security
- ArrayAccess and IteratorAggregate support for intuitive collection usage
- Countable interface for collections
- Context propagation to all collection items
- Read-only collection design for security by design
- Comprehensive test suite (17 new tests) for collection functionality
- New collection security demonstration example (`collection_security.php`)

### Changed
- Enhanced `TemplateVariable::create()` to detect and wrap collections
- Updated documentation with collection security examples
- Enhanced README with dedicated Collection Security section

### Security
- **MAJOR**: Closes security gap in template rendering with collections
- Prevents XSS in foreach loops and array access scenarios
- Extends "secure by default" behavior to complex data structures
- Addresses AppSec Manifesto compliance for collection handling

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