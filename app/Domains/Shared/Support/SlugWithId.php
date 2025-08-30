<?php

namespace App\Domains\Shared\Support;

class SlugWithId
{
    public static function extractId(string $slugWithId): ?int
    {
        if (preg_match('/-(\d+)$/', $slugWithId, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    public static function build(string $baseSlug, int $id): string
    {
        $baseSlug = trim($baseSlug);
        // Remove trailing -{id} only if it matches the provided id, to preserve
        // numeric endings that are part of the title (e.g., "chapter-1").
        $pattern = '/-' . preg_quote((string) $id, '/') . '$/';
        $baseSlug = preg_replace($pattern, '', $baseSlug) ?? $baseSlug;
        return sprintf('%s-%d', $baseSlug, $id);
    }

    public static function isCanonical(string $requested, string $canonical): bool
    {
        return rtrim($requested, '/') === rtrim($canonical, '/');
    }
}
