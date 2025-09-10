<?php

namespace App\Domains\Shared\Support;

use Illuminate\Support\Str;

class SimpleSlug
{
    /**
     * Normalize a human-entered name into the canonical slug format used for profiles.
     */
    public static function normalize(string $name): string
    {
        $name = trim($name);
        // Use Str::slug which lowercases, replaces non-alnum with dashes, and collapses dashes
        $slug = Str::slug($name);
        // Ensure no leading/trailing dashes (Str::slug already does this, but keep defensive)
        $slug = trim($slug, '-');
        return $slug;
    }
}
