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
    /** @var list<string> */
    private readonly array $allowedUrlSchemes;

    /**
     * @param list<string> $allowedUrlSchemes Schemes accepted by the URL_ATTR context (case-insensitive)
     * @param bool $allowProtocolRelative Whether `//host/path` URLs are accepted by URL_ATTR
     * @param string $unsafeUrlPlaceholder Value emitted instead of a rejected URL
     */
    public function __construct(
        array $allowedUrlSchemes = ['http', 'https', 'mailto'],
        private readonly bool $allowProtocolRelative = false,
        private readonly string $unsafeUrlPlaceholder = '#',
    ) {
        $this->allowedUrlSchemes = array_values(array_map('strtolower', $allowedUrlSchemes));
    }

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
            EscapeContext::URL_ATTR => $this->escapeHtmlAttribute($this->sanitizeUrl($value)),
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
            EscapeContext::URL_ATTR => $this->escape($value, $context) !== $value,
            default => true,
        };
    }
    
    /**
     * Return the URL if it is safe to place in an href/src attribute, otherwise the placeholder.
     *
     * Accepts relative references (paths, `?query`, `#fragment`) and absolute URLs whose
     * scheme is allowlisted. Rejects control characters and (Unicode) whitespace or
     * invisible characters, backslash host tricks, protocol-relative URLs (unless enabled),
     * and values whose HTML-entity- or percent-decoded form carries a disallowed scheme.
     */
    public function sanitizeUrl(string $value): string
    {
        $url = trim($value, " \t\n\r\0\x0B\x0C");
        if ($url === '' || !mb_check_encoding($url, 'UTF-8')) {
            return $this->unsafeUrlPlaceholder;
        }
        // Control chars, any Unicode whitespace/separator, format chars (zero-width, bidi)
        if (preg_match('/[\x00-\x20\x7F]|[\p{Z}\p{Cc}\p{Cf}]/u', $url)) {
            return $this->unsafeUrlPlaceholder;
        }
        $decodedEntities = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        foreach ([$url, $decodedEntities, rawurldecode($url), rawurldecode($decodedEntities)] as $candidate) {
            if (!$this->isSafeUrlCandidate($candidate)) {
                return $this->unsafeUrlPlaceholder;
            }
        }
        return $url;
    }

    private function isSafeUrlCandidate(string $url): bool
    {
        if (preg_match('/[\x00-\x1F\x7F]|[\p{Cc}\p{Cf}]/u', $url)) {
            return false;
        }
        // Backslashes are normalised to slashes by browsers: `\\host`, `/\host`
        if (preg_match('#^[/\\\\]{2}#', $url)) {
            return $this->allowProtocolRelative && str_starts_with($url, '//') && !str_contains(substr($url, 0, 3), '\\');
        }
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9+.\-]*):/', $url, $m)) {
            return in_array(strtolower($m[1]), $this->allowedUrlSchemes, true);
        }
        // A colon before the first / ? # would be parsed as a (malformed) scheme
        $firstDelimiter = strcspn($url, '/?#');
        return !str_contains(substr($url, 0, $firstDelimiter), ':');
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