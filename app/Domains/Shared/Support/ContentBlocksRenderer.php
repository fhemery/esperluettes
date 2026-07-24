<?php

declare(strict_types=1);

namespace App\Domains\Shared\Support;

use Illuminate\Support\Facades\Blade;
use Mews\Purifier\Facades\Purifier;

/**
 * Renders MultiEdit advanced content (an ordered array of typed blocks) to
 * sanitized HTML, and computes the plain-text length of its text blocks.
 *
 * Block shapes:
 *   ['type' => 'text',  'html' => '<p>…</p>']
 *   ['type' => 'image', 'path' => 'news/x.jpg', 'alt' => '…', 'caption' => '…'?]
 *
 * Text is sanitized with the `multiedit-text` Purifier profile (no <img>);
 * images render via the shared <x-media::image> component (responsive picture).
 */
class ContentBlocksRenderer
{
    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    public function render(array $blocks): string
    {
        $out = '';
        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;

            if ($type === 'text') {
                $clean = $this->sanitizeText((string) ($block['html'] ?? ''));
                if ($clean !== '') {
                    $out .= '<div class="ce-block ce-block--text">' . $clean . '</div>';
                }
            } elseif ($type === 'image') {
                $path = $block['path'] ?? null;
                if (!$path) {
                    continue;
                }
                $out .= Blade::render(
                    '<x-media::image :path="$path" :alt="$alt" :caption="$caption" :raw="$raw" class="ce-block ce-block--image" />',
                    [
                        'path' => (string) $path,
                        'alt' => (string) ($block['alt'] ?? ''),
                        'caption' => ($block['caption'] ?? null) ?: null,
                        'raw' => !empty($block['keep_original']),
                    ]
                );
            }
        }
        return $out;
    }

    /**
     * Sanitize one text block's HTML with the no-img MultiEdit profile.
     */
    public function sanitizeText(string $html): string
    {
        return (string) Purifier::clean($html, 'multiedit-text');
    }

    /**
     * Plain-text length summed across text blocks only (images excluded),
     * for min/max validation and character counts.
     *
     * @param array<int, array<string, mixed>> $blocks
     */
    public function plainTextLength(array $blocks): int
    {
        $length = 0;
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) !== 'text') {
                continue;
            }
            $text = html_entity_decode(strip_tags((string) ($block['html'] ?? '')), ENT_QUOTES | ENT_HTML5);
            // Collapse whitespace the way a character counter would perceive it.
            $text = preg_replace('/\s+/u', ' ', $text) ?? '';
            $length += mb_strlen(trim($text));
        }
        return $length;
    }
}
