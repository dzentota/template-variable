<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable\Value;

/**
 * Interface for typed value objects that can be used with TemplateVariable
 * 
 * This interface allows integration with various TypedValue implementations
 * while maintaining type safety and supporting the AppSec manifesto principles.
 */
interface TypedValueInterface
{
    /**
     * Get the underlying value as a string representation
     */
    public function getValue(): string;
    
    /**
     * Get the type of the value (for context-aware escaping decisions)
     */
    public function getType(): string;
    
    /**
     * Check if the value is considered safe (already escaped/validated)
     */
    public function isSafe(): bool;
} 