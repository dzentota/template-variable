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
    /** URL component (query value / path segment): rawurlencode(). NOT for whole href/src values. */
    case URL = 'url';
    /** Whole URL inside an HTML attribute (href, src, action...): scheme allowlist + attribute escaping. */
    case URL_ATTR = 'url_attr';
    case RAW = 'raw';
} 