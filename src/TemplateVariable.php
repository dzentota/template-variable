<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable;

use Dzentota\TemplateVariable\Context\EscapeContext;
use Dzentota\TemplateVariable\Escaper\EscaperInterface;
use Dzentota\TemplateVariable\Escaper\ContextAwareEscaper;
use dzentota\TypedValue\Typed;
use Stringable;

/**
 * TemplateVariable class for secure, context-aware template output
 * 
 * This class implements the security principles from the AppSec manifesto by providing
 * automatic context-aware escaping for template variables. It supports both implicit
 * escaping via __toString() and explicit context specification via __invoke().
 * 
 * Smart value handling via create() method:
 * - Scalars, Stringable, and TypedValue objects: wrapped in TemplateVariable for escaping
 * - Arrays and other objects: returned as-is to preserve natural behavior
 * 
 * Usage examples:
 * - $name = TemplateVariable::create('<script>'); echo $name; // auto-escaped
 * - $data = TemplateVariable::create(['key' => 'value']); // returns array as-is
 * - $name('html') - explicitly escape for HTML context
 * - $class('attr') - explicitly escape for HTML attribute context
 * - $script('js') - explicitly escape for JavaScript context
 */
class TemplateVariable
{
    private readonly EscaperInterface $escaper;
    private readonly EscapeContext $defaultContext;
    
    /**
     * Private constructor - use create() static method instead
     * 
     * @param mixed $value The value to be escaped
     * @param EscapeContext $defaultContext The default escaping context (defaults to HTML)
     * @param EscaperInterface|null $escaper Custom escaper (defaults to ContextAwareEscaper)
     */
    private function __construct(
        private readonly mixed $value,
        EscapeContext $defaultContext = EscapeContext::HTML,
        ?EscaperInterface $escaper = null
    ) {
        $this->defaultContext = $defaultContext;
        $this->escaper = $escaper ?? new ContextAwareEscaper();
    }
    
    /**
     * Automatically escape the value using the default context
     * 
     * This method is called when the object is used in a string context,
     * providing automatic escaping without explicit method calls.
     * 
     * @return string The escaped value
     */
    public function __toString(): string
    {
        return $this->escapeValue($this->defaultContext);
    }
    
    /**
     * Explicitly specify the escaping context
     * 
     * This method allows explicit context specification:
     * - $name('html') for HTML content
     * - $class('attr') for HTML attributes
     * - $script('js') for JavaScript context
     * - $url('url') for URL context
     * - $style('css') for CSS context
     * - $raw('raw') for raw, unescaped output
     * 
     * @param string $context The escaping context
     * @return string The escaped value
     * @throws \InvalidArgumentException If the context is not valid
     */
    public function __invoke(string $context): string
    {
        $escapeContext = $this->parseContext($context);
        return $this->escapeValue($escapeContext);
    }
    
    /**
     * Get the raw value without any escaping
     * 
     * @return mixed The raw value
     */
    public function getRaw(): mixed
    {
        return $this->value;
    }
    
    /**
     * Check if the value needs escaping for a specific context
     * 
     * @param string|EscapeContext $context
     * @return bool
     */
    public function needsEscaping(string|EscapeContext $context): bool
    {
        $escapeContext = $context instanceof EscapeContext ? $context : $this->parseContext($context);
        
        // Scalar values (except strings) and non-Stringable objects don't need escaping as they're not tainted
        $processedValue = $this->processValue();
        if (!is_string($processedValue) && !($processedValue instanceof Stringable)) {
            return false;
        }
        
        $stringValue = is_string($processedValue) ? $processedValue : (string) $processedValue;
        return $this->escaper->needsEscaping($stringValue, $escapeContext);
    }
    
    /**
     * Process the value according to its type
     * 
     * @return mixed The processed value
     */
    private function processValue(): mixed
    {
        // Handle TypedValue objects (dzentota\TypedValue\Typed interface)
        if ($this->value instanceof Typed) {
            return $this->value->toNative();
        }
        
        // Return value as-is
        return $this->value;
    }
    
    /**
     * Get the underlying value as a string
     * 
     * @return string
     */
    private function getStringValue(): string
    {
        $processedValue = $this->processValue();
        
        // Convert to string representation
        return match (true) {
            is_string($processedValue) => $processedValue,
            $processedValue instanceof Stringable => (string) $processedValue,
            is_bool($processedValue) => $processedValue ? '1' : '',
            is_null($processedValue) => '',
            is_scalar($processedValue) => (string) $processedValue,
            default => throw new \InvalidArgumentException(
                'Cannot convert ' . get_debug_type($processedValue) . ' to string. ' .
                'Use TemplateVariable::fromString(), fromTypedValue(), fromScalar(), or fromMixed() ' .
                'for explicit type handling.'
            )
        };
    }
    
    /**
     * Escape the value for the specified context
     * 
     * @param EscapeContext $context
     * @return string
     */
    private function escapeValue(EscapeContext $context): string
    {
        $processedValue = $this->processValue();
        
        // Scalar values (except strings) and non-Stringable objects are not tainted
        if (!is_string($processedValue) && !($processedValue instanceof Stringable)) {
            return $this->getStringValue();
        }
        
        // String values and Stringable objects need escaping
        $stringValue = is_string($processedValue) ? $processedValue : (string) $processedValue;
        return $this->escaper->escape($stringValue, $context);
    }
    
    /**
     * Parse a string context into an EscapeContext enum
     * 
     * @param string $context
     * @return EscapeContext
     * @throws \InvalidArgumentException
     */
    private function parseContext(string $context): EscapeContext
    {
        return match($context) {
            'html', 'h' => EscapeContext::HTML,
            'attr', 'attribute', 'a' => EscapeContext::ATTRIBUTE,
            'css', 'style', 'c' => EscapeContext::CSS,
            'js', 'javascript', 'script', 'j' => EscapeContext::JAVASCRIPT,
            'url', 'uri', 'u' => EscapeContext::URL,
            'raw', 'none', 'r' => EscapeContext::RAW,
            default => throw new \InvalidArgumentException("Unknown escape context: {$context}")
        };
    }
    
    /**
     * Create a TemplateVariable or return the value as-is for non-escapable types
     * 
     * For scalars, Stringable objects, and TypedValue objects, creates a TemplateVariable for escaping.
     * For arrays and other objects, returns the value as-is to preserve behavior.
     * 
     * @param mixed $value
     * @param EscapeContext $defaultContext
     * @param EscaperInterface|null $escaper
     * @return mixed TemplateVariable instance or the original value
     */
    public static function create(
        mixed $value,
        EscapeContext $defaultContext = EscapeContext::HTML,
        ?EscaperInterface $escaper = null
    ): mixed {
        if (!is_scalar($value) && !($value instanceof Typed) && !($value instanceof Stringable)) {
            return $value;
        }
        return new static($value, $defaultContext, $escaper);
    }
    
    /**
     * Create a TemplateVariable with HTML context (most common case)
     * 
     * @param mixed $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function html(mixed $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::HTML, $escaper);
    }
    
    /**
     * Create a TemplateVariable with attribute context
     * 
     * @param mixed $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function attr(mixed $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::ATTRIBUTE, $escaper);
    }
    
    /**
     * Create a TemplateVariable with JavaScript context
     * 
     * @param mixed $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function js(mixed $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::JAVASCRIPT, $escaper);
    }
    
    /**
     * Create a TemplateVariable with CSS context
     * 
     * @param mixed $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function css(mixed $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::CSS, $escaper);
    }
    
    /**
     * Create a TemplateVariable with URL context
     * 
     * @param mixed $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function url(mixed $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::URL, $escaper);
    }
    
    /**
     * Create a TemplateVariable with raw context (no escaping)
     * 
     * @param mixed $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function raw(mixed $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::RAW, $escaper);
    }
} 