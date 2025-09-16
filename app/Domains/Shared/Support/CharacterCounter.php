<?php

namespace App\Domains\Shared\Support;

/**
 * CharacterCounter: strips HTML and counts characters in a Unicode-aware way.
 *
 * Steps:
 * - Strip HTML tags and decode HTML entities to text.
 * - Return the UTF-8 length of the resulting string (includes spaces and punctuation).
 */
final class CharacterCounter
{
    public static function count(string $htmlOrText): int
    {
        if ($htmlOrText === '') {
            return 0;
        }

        // Strip HTML and decode entities
        $stripped = strip_tags($htmlOrText);
        $text = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Unicode-aware length
        return mb_strlen($text, 'UTF-8');
    }
}
