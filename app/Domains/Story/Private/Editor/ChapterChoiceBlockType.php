<?php

declare(strict_types=1);

namespace App\Domains\Story\Private\Editor;

use App\Domains\Editor\Public\Blocks\EditorBlockType;

/**
 * `chapter-choice` block: a group of links to other chapters of the same story.
 * ['type' => 'chapter-choice', 'choices' => [['chapter_id' => int, 'label' => ?string, 'enabled' => bool], …]].
 *
 * Rendered at save time from $context['chapters'] (ChapterChoiceTargets):
 * disabled choices and choices to a chapter absent from the map (deleted) are
 * skipped; an empty label falls back to the target's title of that moment.
 */
final class ChapterChoiceBlockType implements EditorBlockType
{
    public function key(): string
    {
        return 'chapter-choice';
    }

    public function labelKey(): string
    {
        return 'story::chapters.choice.block_label';
    }

    public function icon(): string
    {
        return 'alt_route';
    }

    public function editorView(): string
    {
        return 'story::editor.chapter-choice-block';
    }

    public function render(array $block, array $context): string
    {
        $chapters = is_array($context['chapters'] ?? null) ? $context['chapters'] : [];
        $links = [];
        foreach ((array) ($block['choices'] ?? []) as $choice) {
            if (!is_array($choice) || empty($choice['enabled'])) {
                continue;
            }
            $target = $chapters[(int) ($choice['chapter_id'] ?? 0)] ?? null;
            if ($target === null) {
                continue;
            }
            $label = trim((string) ($choice['label'] ?? ''));
            $links[] = [
                'url' => $target['url'],
                'text' => $label !== '' ? $label : $target['title'],
            ];
        }

        if ($links === []) {
            return '';
        }

        return view('story::editor.chapter-choice-render', ['links' => $links])->render();
    }
}
