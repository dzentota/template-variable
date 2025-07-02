<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable;

use Dzentota\TemplateVariable\Context\EscapeContext;
use Dzentota\TemplateVariable\Escaper\EscaperInterface;
use Dzentota\TemplateVariable\Escaper\HtmlEscaper;
use Dzentota\TemplateVariable\Value\TypedValueInterface;

/**
 * TemplateVariable class for secure, context-aware template output
 * 
 * This class implements the security principles from the AppSec manifesto by providing
 * automatic context-aware escaping for template variables. It supports both implicit
 * escaping via __toString() and explicit context specification via __invoke().
 * 
 * Usage examples:
 * - $name->__toString() or (string)$name - auto-escapes for HTML context
 * - $name('html') - explicitly escape for HTML context
 * - $class('attr') - explicitly escape for HTML attribute context
 * - $script('js') - explicitly escape for JavaScript context
 */
class TemplateVariable
{
    private readonly EscaperInterface $escaper;
    private readonly EscapeContext $defaultContext;
    
    /**
     * @param string|TypedValueInterface $value The value to be escaped
     * @param EscapeContext $defaultContext The default escaping context (defaults to HTML)
     * @param EscaperInterface|null $escaper Custom escaper (defaults to HtmlEscaper)
     */
    public function __construct(
        private readonly string|TypedValueInterface $value,
        EscapeContext $defaultContext = EscapeContext::HTML,
        ?EscaperInterface $escaper = null
    ) {
        $this->defaultContext = $defaultContext;
        $this->escaper = $escaper ?? new HtmlEscaper();
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
     * @return string The raw value
     */
    public function getRaw(): string
    {
        return $this->getStringValue();
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
        
        // If the value is already safe and we have a TypedValue, check its safety
        if ($this->value instanceof TypedValueInterface && $this->value->isSafe()) {
            return false;
        }
        
        return $this->escaper->needsEscaping($this->getStringValue(), $escapeContext);
    }
    
    /**
     * Get the underlying value as a string
     * 
     * @return string
     */
    private function getStringValue(): string
    {
        if ($this->value instanceof TypedValueInterface) {
            return $this->value->getValue();
        }
        
        return $this->value;
    }
    
    /**
     * Escape the value for the specified context
     * 
     * @param EscapeContext $context
     * @return string
     */
    private function escapeValue(EscapeContext $context): string
    {
        $stringValue = $this->getStringValue();
        
        // If the value is marked as safe (from TypedValue), return it as-is for most contexts
        // Safe values are assumed to be already properly escaped for their intended use
        if ($this->value instanceof TypedValueInterface && $this->value->isSafe()) {
            return $stringValue;
        }
        
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
     * Create a TemplateVariable with HTML context (most common case)
     * 
     * @param string|TypedValueInterface $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function html(string|TypedValueInterface $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::HTML, $escaper);
    }
    
    /**
     * Create a TemplateVariable with attribute context
     * 
     * @param string|TypedValueInterface $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function attr(string|TypedValueInterface $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::ATTRIBUTE, $escaper);
    }
    
    /**
     * Create a TemplateVariable with JavaScript context
     * 
     * @param string|TypedValueInterface $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function js(string|TypedValueInterface $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::JAVASCRIPT, $escaper);
    }
    
    /**
     * Create a TemplateVariable with CSS context
     * 
     * @param string|TypedValueInterface $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function css(string|TypedValueInterface $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::CSS, $escaper);
    }
    
    /**
     * Create a TemplateVariable with URL context
     * 
     * @param string|TypedValueInterface $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function url(string|TypedValueInterface $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::URL, $escaper);
    }
    
    /**
     * Create a TemplateVariable with raw (no escaping) context
     * 
     * @param string|TypedValueInterface $value
     * @param EscaperInterface|null $escaper
     * @return static
     */
    public static function raw(string|TypedValueInterface $value, ?EscaperInterface $escaper = null): static
    {
        return new static($value, EscapeContext::RAW, $escaper);
    }
} 