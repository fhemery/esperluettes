<?php

declare(strict_types=1);

namespace App\Domains\Editor\Private\Support;

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
 * Text is sanitized with a no-<img> Purifier profile — `multiedit-text` by
 * default, or any profile the consumer passes; images render via the shared
 * <x-media::image> component (responsive picture).
 */
class ContentBlocksRenderer
{
    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    public function render(array $blocks, string $profile = 'multiedit-text'): string
    {
        $out = '';
        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;

            if ($type === 'text') {
                $clean = $this->sanitizeText((string) ($block['html'] ?? ''), $profile);
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
     * Sanitize one text block's HTML with a no-img MultiEdit profile.
     */
    public function sanitizeText(string $html, string $profile = 'multiedit-text'): string
    {
        return (string) Purifier::clean($html, $profile);
    }

    /**
     * Concatenated `html` of text blocks only, in document order, returned
     * exactly as stored: no tag stripping, no whitespace collapsing, no
     * trimming. Consumers apply their own counter to it — unlike
     * plainTextLength(), which normalises and so cannot be used for counts that
     * must stay stable across a conversion.
     *
     * @param array<int, array<string, mixed>> $blocks
     */
    public function plainText(array $blocks): string
    {
        $out = '';
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) !== 'text') {
                continue;
            }
            $out .= (string) ($block['html'] ?? '');
        }
        return $out;
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
