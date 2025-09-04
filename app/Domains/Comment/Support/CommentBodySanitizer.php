<?php

declare(strict_types=1);

namespace App\Domains\Comment\Support;

use Mews\Purifier\Facades\Purifier;

final class CommentBodySanitizer
{
    private const PROFILE = 'strict';

    /**
     * Sanitize the provided HTML body according to the configured profile.
     */
    public function sanitizeToHtml(string $body): string
    {
        $clean = Purifier::clean($body, self::PROFILE);
        $html = is_string($clean) ? $clean : '';
        return trim($html);
    }

    /**
     * Return the plain text length of the sanitized HTML body.
     */
    public function plainTextLength(string $body): int
    {
        $html = $this->sanitizeToHtml($body);
        $plain = trim(strip_tags($html));
        return mb_strlen($plain);
    }
}
