<?php

namespace App\Domains\Shared\Support;

/**
 * WordCounter: strips HTML and counts words in a language-agnostic way.
 *
 * Rules implemented:
 * - Strip HTML tags and decode HTML entities.
 * - Tokenize on any non-letter/digit characters (Unicode-aware),
 *   which naturally handles French punctuation spacing.
 * - Hyphenated tokens are split (e.g., "state-of-the-art" → 4).
 * - Apostrophes/quotes split tokens (e.g., "l'amour", «aujourd’hui») → separate words.
 * - Numbers are considered words (e.g., "2" in "Version 2.0").
 */
final class WordCounter
{
    /**
     * Count words in the provided HTML or plain text.
     */
    public static function count(string $htmlOrText): int
    {
        if ($htmlOrText === '') {
            return 0;
        }

        // 1) Strip HTML tags then decode entities
        $stripped = strip_tags($htmlOrText);
        $text = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize various Unicode quote characters to simple separators by leaving them
        // for the tokenizer which splits on any non-letter/digit.

        // 2) Split on any run of non-letter/digit characters. Unicode-aware.
        // This treats hyphens, apostrophes, quotes, punctuation and spaces as separators.
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($tokens) ? count($tokens) : 0;
    }
}
