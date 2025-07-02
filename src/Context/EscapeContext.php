<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable\Context;

/**
 * Defines the available escaping contexts for template variables
 * 
 * This enum supports the AppSec manifesto by providing explicit context-aware escaping
 * to prevent XSS vulnerabilities in different output contexts.
 */
enum EscapeContext: string
{
    case HTML = 'html';
    case ATTRIBUTE = 'attr';
    case CSS = 'css';
    case JAVASCRIPT = 'js';
    case URL = 'url';
    case RAW = 'raw';
} 