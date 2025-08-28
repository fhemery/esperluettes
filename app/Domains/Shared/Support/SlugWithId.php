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
        // Remove any trailing -{id} if present before appending the real id
        $baseSlug = preg_replace('/-(\d+)$/', '', $baseSlug) ?? $baseSlug;
        return sprintf('%s-%d', $baseSlug, $id);
    }

    public static function isCanonical(string $requested, string $canonical): bool
    {
        return rtrim($requested, '/') === rtrim($canonical, '/');
    }
}
