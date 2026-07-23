<?php

namespace App\Domains\Quote\Private\Support;

use Mews\Purifier\Facades\Purifier;

class QuoteNoteSanitizer
{
    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = Purifier::clean($html, 'quote-note');

        return trim($clean) === '' ? null : $clean;
    }
}
