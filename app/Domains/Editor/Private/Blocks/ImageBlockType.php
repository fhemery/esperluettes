<?php

declare(strict_types=1);

namespace App\Domains\Editor\Private\Blocks;

use App\Domains\Editor\Public\Blocks\EditorBlockType;
use Illuminate\Support\Facades\Blade;

/**
 * Built-in `image` block:
 * ['type' => 'image', 'path' => 'news/x.jpg', 'alt' => '…', 'caption' => '…'?, 'keep_original' => bool?].
 * Rendered via the shared <x-media::image> component (responsive picture).
 */
final class ImageBlockType implements EditorBlockType
{
    public function key(): string
    {
        return 'image';
    }

    public function labelKey(): string
    {
        return 'editor::multi.add_image';
    }

    public function icon(): string
    {
        return 'image';
    }

    public function editorView(): string
    {
        return 'editor::components.multi._image-block';
    }

    public function render(array $block, array $context): string
    {
        $path = $block['path'] ?? null;
        if (!$path) {
            return '';
        }
        return Blade::render(
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
