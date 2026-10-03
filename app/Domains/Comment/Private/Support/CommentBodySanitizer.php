<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Support;

use Mews\Purifier\Facades\Purifier;

final class CommentBodySanitizer
{
    /** Comment bodies: lists, blockquotes, alignment, emoji. */
    public const STRICT = 'strict';

    /** Annotation bodies: bold, italic, emoji, paragraphs and line breaks only. */
    public const ANNOTATION = 'annotation';

    /**
     * Sanitize the provided HTML body according to the given purifier profile.
     */
    public function sanitizeToHtml(string $body, string $profile = self::STRICT): string
    {
        $clean = Purifier::clean($body, $profile);
        $html = is_string($clean) ? $clean : '';
        return trim($html);
    }

    /**
     * Return the plain text length of the sanitized HTML body.
     */
    public function plainTextLength(string $body, string $profile = self::STRICT): int
    {
        $html = $this->sanitizeToHtml($body, $profile);
        $plain = trim(strip_tags($html));
        return mb_strlen($plain);
    }
}
