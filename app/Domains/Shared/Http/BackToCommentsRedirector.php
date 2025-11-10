<?php

declare(strict_types=1);

namespace App\Domains\Shared\Http;

final class BackToCommentsRedirector
{
    /**
     * Build a relative URL to the previous page with the #comments anchor.
     * The fragment is not sent by browsers, so we reconstruct from the previous URL.
     */
    public static function build(): string
    {
        $base = url()->previous(); // e.g. http://localhost/default/123?param=1, fragments are never transmitted
        $path = parse_url($base, PHP_URL_PATH) ?: '/';
        $query = parse_url($base, PHP_URL_QUERY) ?: null;
        $relative = './' . ltrim((string) $path, '/');
        $qs = $query ? ('?' . $query) : '';

        return $relative . $qs . '#comments';
    }
}
