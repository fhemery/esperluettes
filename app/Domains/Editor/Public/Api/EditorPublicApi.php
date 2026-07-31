<?php

declare(strict_types=1);

namespace App\Domains\Editor\Public\Api;

use App\Domains\Editor\Private\Support\ContentBlocksRenderer;

/**
 * Sole entry point other domains use for MultiEdit content blocks.
 *
 * Blocks are plain arrays; their schema is documented in the domain README.
 */
final class EditorPublicApi
{
    public function __construct(
        private readonly ContentBlocksRenderer $renderer,
    ) {}

    /**
     * Render an ordered array of typed blocks to sanitized HTML.
     *
     * @param array<int, array<string, mixed>> $blocks
     * @param string $profile Purifier profile applied to text blocks
     */
    public function render(array $blocks, string $profile = 'multiedit-text'): string
    {
        return $this->renderer->render($blocks, $profile);
    }

    /**
     * Sanitize one text block's HTML with a no-img MultiEdit profile.
     */
    public function sanitizeText(string $html, string $profile = 'multiedit-text'): string
    {
        return $this->renderer->sanitizeText($html, $profile);
    }

    /**
     * Concatenated `html` of text blocks only, in order, unmodified.
     *
     * @param array<int, array<string, mixed>> $blocks
     */
    public function plainText(array $blocks): string
    {
        return $this->renderer->plainText($blocks);
    }

    /**
     * Plain-text length summed across text blocks only (images excluded).
     *
     * @param array<int, array<string, mixed>> $blocks
     */
    public function plainTextLength(array $blocks): int
    {
        return $this->renderer->plainTextLength($blocks);
    }
}
