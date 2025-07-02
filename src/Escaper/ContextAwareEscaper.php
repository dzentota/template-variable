<?php

declare(strict_types=1);

namespace Dzentota\TemplateVariable\Escaper;

use Dzentota\TemplateVariable\Context\EscapeContext;

/**
 * Context-aware escaper implementation
 * 
 * This class implements secure escaping strategies for different output contexts
 * in accordance with the AppSec manifesto principles.
 */
class ContextAwareEscaper implements EscaperInterface
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
        $value = str_replace('<', '\\3C ', $value);
        $value = str_replace('>', '\\3E ', $value);
        $value = str_replace('&', '\\26 ', $value);
        $value = str_replace("\n", '\\A ', $value);
        $value = str_replace("\r", '\\D ', $value);
        $value = str_replace("\t", '\\9 ', $value);
        
        return $value;
    }
    
    /**
     * Escape JavaScript content (without adding quotes)
     * 
     * Based on Laminas Escaper approach for more flexible JavaScript escaping
     */
    private function escapeJavaScript(string $value): string
    {
        // Character-by-character escaping for JavaScript context
        // This approach doesn't add quotes, making it more flexible
        $escaped = '';
        $length = mb_strlen($value, 'UTF-8');
        
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($value, $i, 1, 'UTF-8');
            $ord = mb_ord($char, 'UTF-8');
            
            // Handle special JavaScript characters
            switch ($char) {
                case '"':
                    $escaped .= '\\"';
                    break;
                case "'":
                    $escaped .= "\\'";
                    break;
                case '\\':
                    $escaped .= '\\\\';
                    break;
                case '/':
                    $escaped .= '\\/';
                    break;
                case "\n":
                    $escaped .= '\\n';
                    break;
                case "\r":
                    $escaped .= '\\r';
                    break;
                case "\t":
                    $escaped .= '\\t';
                    break;
                case "\f":
                    $escaped .= '\\f';
                    break;
                case "\b":
                    $escaped .= '\\b';
                    break;
                case '<':
                    $escaped .= '\\u003C';
                    break;
                case '>':
                    $escaped .= '\\u003E';
                    break;
                case '&':
                    $escaped .= '\\u0026';
                    break;
                default:
                    // Handle control characters and high Unicode
                    if ($ord < 32 || $ord > 126) {
                        $escaped .= sprintf('\\u%04X', $ord);
                    } else {
                        $escaped .= $char;
                    }
                    break;
            }
        }
        
        return $escaped;
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
               str_contains($value, '<') ||
               str_contains($value, '>') ||
               str_contains($value, '&') ||
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
               str_contains($value, '\\') || 
               str_contains($value, '/') || 
               preg_match('/[\x00-\x1F\x7F-\x9F]/', $value);
    }
    
    /**
     * Check if value contains URL special characters
     */
    private function containsUrlSpecialChars(string $value): bool
    {
        return $value !== rawurldecode(rawurlencode($value));
    }
} 