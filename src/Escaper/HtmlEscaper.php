<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable\Escaper;

use Dzentota\TemplateVariable\Context\EscapeContext;

/**
 * HTML context-aware escaper implementation
 * 
 * This class implements secure escaping strategies for different output contexts
 * in accordance with the AppSec manifesto principles.
 */
class HtmlEscaper implements EscaperInterface
{
    /**
     * Escape a value for the specified context
     */
    public function escape(string $value, EscapeContext $context): string
    {
        return match($context) {
            EscapeContext::HTML => $this->escapeHtml($value),
            EscapeContext::ATTRIBUTE => $this->escapeHtmlAttribute($value),
            EscapeContext::CSS => $this->escapeCss($value),
            EscapeContext::JAVASCRIPT => $this->escapeJavaScript($value),
            EscapeContext::URL => $this->escapeUrl($value),
            EscapeContext::RAW => $value, // Raw context - no escaping
        };
    }
    
    /**
     * Check if a value needs escaping for the given context
     */
    public function needsEscaping(string $value, EscapeContext $context): bool
    {
        if ($context === EscapeContext::RAW) {
            return false;
        }
        
        // Check for characters that need escaping in each context
        return match($context) {
            EscapeContext::HTML => $this->containsHtmlSpecialChars($value),
            EscapeContext::ATTRIBUTE => $this->containsAttributeSpecialChars($value),
            EscapeContext::CSS => $this->containsCssSpecialChars($value),
            EscapeContext::JAVASCRIPT => $this->containsJsSpecialChars($value),
            EscapeContext::URL => $this->containsUrlSpecialChars($value),
            default => true,
        };
    }
    
    /**
     * Escape HTML content
     */
    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Escape HTML attribute content
     */
    private function escapeHtmlAttribute(string $value): string
    {
        // For attributes, we need to be more aggressive
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Escape CSS content (basic implementation)
     */
    private function escapeCss(string $value): string
    {
        // Basic CSS escaping - escape special CSS characters
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace('"', '\\"', $value);
        $value = str_replace("'", "\\'", $value);
        $value = str_replace("\n", '\\A ', $value);
        $value = str_replace("\r", '\\D ', $value);
        $value = str_replace("\t", '\\9 ', $value);
        
        return $value;
    }
    
    /**
     * Escape JavaScript content
     */
    private function escapeJavaScript(string $value): string
    {
        // JSON encode provides safe JavaScript escaping
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Escape URL content
     */
    private function escapeUrl(string $value): string
    {
        return rawurlencode($value);
    }
    
    /**
     * Check if value contains HTML special characters
     */
    private function containsHtmlSpecialChars(string $value): bool
    {
        return str_contains($value, '<') || 
               str_contains($value, '>') || 
               str_contains($value, '&') || 
               str_contains($value, '"') || 
               str_contains($value, "'");
    }
    
    /**
     * Check if value contains attribute special characters
     */
    private function containsAttributeSpecialChars(string $value): bool
    {
        return $this->containsHtmlSpecialChars($value);
    }
    
    /**
     * Check if value contains CSS special characters
     */
    private function containsCssSpecialChars(string $value): bool
    {
        return str_contains($value, '\\') || 
               str_contains($value, '"') || 
               str_contains($value, "'") || 
               str_contains($value, "\n") || 
               str_contains($value, "\r") || 
               str_contains($value, "\t");
    }
    
    /**
     * Check if value contains JavaScript special characters
     */
    private function containsJsSpecialChars(string $value): bool
    {
        return str_contains($value, '<') || 
               str_contains($value, '>') || 
               str_contains($value, '&') || 
               str_contains($value, '"') || 
               str_contains($value, "'") || 
               str_contains($value, '\\');
    }
    
    /**
     * Check if value contains URL special characters
     */
    private function containsUrlSpecialChars(string $value): bool
    {
        return preg_match('/[^A-Za-z0-9\-_.~]/', $value) === 1;
    }
} 