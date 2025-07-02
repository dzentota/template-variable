<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable\Value;

/**
 * Simple implementation of TypedValueInterface for demonstration purposes
 * 
 * This class provides a basic implementation that can be used as a reference
 * or for simple use cases where a full TypedValue library is not needed.
 */
class SimpleTypedValue implements TypedValueInterface
{
    /**
     * @param string $value The underlying value
     * @param string $type The type of the value
     * @param bool $isSafe Whether the value is considered safe (already escaped/validated)
     */
    public function __construct(
        private readonly string $value,
        private readonly string $type = 'string',
        private readonly bool $isSafe = false
    ) {
    }
    
    /**
     * Get the underlying value as a string representation
     */
    public function getValue(): string
    {
        return $this->value;
    }
    
    /**
     * Get the type of the value
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Check if the value is considered safe (already escaped/validated)
     */
    public function isSafe(): bool
    {
        return $this->isSafe;
    }
    
    /**
     * Create a safe typed value (pre-escaped/validated)
     */
    public static function safe(string $value, string $type = 'string'): self
    {
        return new self($value, $type, true);
    }
    
    /**
     * Create an unsafe typed value (needs escaping)
     */
    public static function unsafe(string $value, string $type = 'string'): self
    {
        return new self($value, $type, false);
    }
} 