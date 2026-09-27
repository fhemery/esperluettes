<?php

declare(strict_types=1);

namespace App\Domains\Editor\Private\Blocks;

use App\Domains\Editor\Public\Blocks\EditorBlockType;
use Mews\Purifier\Facades\Purifier;

/**
 * Built-in `text` block: ['type' => 'text', 'html' => '<p>…</p>'].
 * Sanitized with the Purifier profile passed under $context['profile'].
 */
final class TextBlockType implements EditorBlockType
{
    public function key(): string
    {
        return 'text';
    }

    public function labelKey(): string
    {
        return 'editor::multi.add_text';
    }

    public function icon(): string
    {
        return 'notes';
    }

    public function editorView(): string
    {
        return 'editor::components.multi._text-block';
    }

    public function render(array $block, array $context): string
    {
        $clean = (string) Purifier::clean((string) ($block['html'] ?? ''), $context['profile']);
        if ($clean === '') {
            return '';
        }
        return '<div class="ce-block ce-block--text">' . $clean . '</div>';
    }
}
