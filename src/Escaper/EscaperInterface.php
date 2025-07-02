<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable\Escaper;

use Dzentota\TemplateVariable\Context\EscapeContext;

/**
 * Interface for context-aware escaping functionality
 * 
 * This interface supports the AppSec manifesto by providing secure escaping
 * for different output contexts to prevent XSS vulnerabilities.
 */
interface EscaperInterface
{
    /**
     * Escape a value for the specified context
     */
    public function escape(string $value, EscapeContext $context): string;
    
    /**
     * Check if a value needs escaping for the given context
     */
    public function needsEscaping(string $value, EscapeContext $context): bool;
} 