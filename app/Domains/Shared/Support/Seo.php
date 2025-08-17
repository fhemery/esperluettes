<?php

namespace App\Domains\Shared\Support;

class Seo
{
    /**
     * Build a clean excerpt for meta descriptions.
     * - Strips HTML
     * - Collapses whitespace
     * - Truncates to $max characters, adding ellipsis when needed
     */
    public static function excerpt(?string $html, int $max = 160): string
    {
        $text = trim((string) strip_tags($html ?? ''));
        $text = preg_replace('/\s+/', ' ', $text ?? '') ?? '';
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        // avoid cutting mid-word harshly if possible
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > $max - 30) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }
        return rtrim($cut, "\s.,;:!?") . '…';
    }
}
