<?php

namespace App\Support;

/**
 * OWASP A03 Anti-XSS Sanitizer for Rich Text & Article Content.
 */
class HtmlSanitizer
{
    protected static array $allowedTags = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 'strike', 's',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'ul', 'ol', 'li', 'a',
        'hr', 'code', 'pre', 'span',
    ];

    public static function clean(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // 1. Remove script, style, iframe tags along with their inner content
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);

        // 2. Strip all remaining disallowed tags
        $allowedTagString = '<'.implode('><', self::$allowedTags).'>';
        $cleaned = strip_tags($html, $allowedTagString);

        // 3. Remove all inline event handlers (onerror, onload, onclick, onmouseover, etc.)
        $cleaned = preg_replace('/\s+on[a-zA-Z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $cleaned);

        // 4. Remove dangerous protocols (javascript:, vbscript:, data:)
        $cleaned = preg_replace_callback('/(href|src)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', function ($match) {
            $val = strtolower($match[0]);
            if (str_contains($val, 'javascript:') || str_contains($val, 'vbscript:') || str_contains($val, 'data:')) {
                return '';
            }

            return $match[0];
        }, $cleaned);

        // 5. Secure <a> links with rel="noopener noreferrer"
        $cleaned = preg_replace_callback('/<a\s+([^>]+)>/i', function ($matches) {
            $attrs = $matches[1];
            if (! preg_match('/\brel\s*=/i', $attrs)) {
                $attrs .= ' rel="noopener noreferrer"';
            }

            return '<a '.trim($attrs).'>';
        }, $cleaned);

        return trim($cleaned);
    }

    public static function cleanPlainText(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Remove script, style, and iframe tags along with their contents
        $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $text);
        $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);
        $text = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $text);

        return trim(strip_tags($text));
    }
}
