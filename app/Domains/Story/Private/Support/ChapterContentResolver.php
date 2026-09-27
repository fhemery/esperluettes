<?php

declare(strict_types=1);

namespace App\Domains\Story\Private\Support;

use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Shared\Support\HtmlLinkUtils;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Turns a submitted chapter payload into the two persisted content fields.
 *
 * Simple mode: `content` is the already-purified author HTML and
 * `content_blocks` is null. Advanced mode: the submitted blocks are normalized
 * (text sanitized once with the narrative profile, images stored or reused),
 * persisted in `content_blocks`, and rendered into `content` — the display
 * cache the reading page prints.
 *
 * `chapter-choice` blocks are normalised (choices without a target dropped,
 * then the block if none is left) and their targets checked: a choice may only
 * point to a chapter of the same story, or to an id the edited chapter already
 * stored (a since-deleted target survives a save). Links, titles and URLs are
 * frozen into `content` at this point — nothing is re-resolved at read time.
 */
class ChapterContentResolver
{
    public function __construct(
        private readonly EditorPublicApi $editor,
        private readonly MediaPublicApi $media,
        private readonly ChapterChoiceTargets $choiceTargets,
    ) {}

    /**
     * @param array<string,mixed> $data
     * @param Chapter|null $chapter the chapter being edited, null on create
     * @return array{content: string, content_blocks: ?array<int,array<string,mixed>>}
     */
    public function resolve(array $data, int $actingUserId, Story $story, ?Chapter $chapter): array
    {
        if (($data['mode'] ?? 'simple') !== 'advanced') {
            return [
                'content' => (string) ($data['content'] ?? ''),
                'content_blocks' => null,
            ];
        }

        // The upload scope is derived from the acting user, never from the
        // request: a client may not name someone else's folder.
        $scope = 'chapters/' . $actingUserId;

        $order = array_values(array_filter(
            explode(',', (string) ($data['blocks_order'] ?? '')),
            fn ($uid) => $uid !== ''
        ));
        $raw = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];

        $blocks = [];
        foreach ($order as $uid) {
            $b = $raw[$uid] ?? null;
            if (!is_array($b)) {
                continue;
            }

            $type = $b['type'] ?? null;

            if ($type === 'text') {
                $html = $this->editor->sanitizeText((string) ($b['html'] ?? ''), 'multiedit-narrative');
                $html = (string) HtmlLinkUtils::stripExternalLinks($html);
                if (trim(strip_tags($html)) === '') {
                    continue; // drop empty text block
                }
                $blocks[] = ['type' => 'text', 'html' => $html];
            } elseif ($type === 'image') {
                $keep = !empty($b['keep_original']);
                $file = $b['file'] ?? null;
                if ($file instanceof UploadedFile) {
                    // "keep original" stores the file without generating variants.
                    $path = $this->media->store($scope, $file, $keep ? [] : [400, 800]);
                } else {
                    $path = !empty($b['path']) ? (string) $b['path'] : null;
                    // A reused image with no variants must render raw, whatever the box says.
                    if ($path && !$keep && !$this->media->hasVariants($path)) {
                        $keep = true;
                    }
                }
                if (!$path) {
                    continue; // drop empty image block
                }
                $block = ['type' => 'image', 'path' => $path, 'alt' => trim((string) ($b['alt'] ?? ''))];
                if ($keep) {
                    $block['keep_original'] = true;
                }
                if (!empty($b['caption'])) {
                    $block['caption'] = (string) $b['caption'];
                }
                $blocks[] = $block;
            } elseif ($type === 'chapter-choice') {
                $choices = $this->normaliseChoices($b['choices'] ?? null);
                if ($choices === []) {
                    continue; // drop a block left without choice
                }
                $blocks[] = ['type' => 'chapter-choice', 'choices' => $choices];
            }
        }

        if ($blocks === []) {
            throw ValidationException::withMessages([
                'blocks' => __('story::validation.chapter.blocks.required'),
            ]);
        }

        $targets = $this->choiceTargets->forStory($story);
        $this->assertChoiceTargets($blocks, $targets, $chapter);

        return [
            'content' => $this->editor->render($blocks, 'multiedit-narrative', ['chapters' => $targets]),
            'content_blocks' => $blocks,
        ];
    }

    /**
     * @return list<array{chapter_id: int, label: ?string, enabled: bool}>
     */
    private function normaliseChoices(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $choices = [];
        foreach ($raw as $choice) {
            if (!is_array($choice)) {
                continue;
            }
            $id = $choice['chapter_id'] ?? null;
            if (!is_numeric($id) || (int) $id <= 0) {
                continue; // drop a choice without target
            }
            $label = trim((string) ($choice['label'] ?? ''));
            $choices[] = [
                'chapter_id' => (int) $id,
                'label' => $label !== '' ? $label : null,
                'enabled' => array_key_exists('enabled', $choice) && $choice['enabled'] !== null
                    ? filter_var($choice['enabled'], FILTER_VALIDATE_BOOLEAN)
                    : true,
            ];
        }

        return $choices;
    }

    /**
     * Security: a choice may only target a chapter of this story, or an id the
     * edited chapter already stored (how a deleted target survives a save).
     *
     * @param array<int, array<string, mixed>> $blocks
     * @param array<int, mixed> $targets
     */
    private function assertChoiceTargets(array $blocks, array $targets, ?Chapter $chapter): void
    {
        $allowed = array_fill_keys(array_keys($targets), true);
        foreach ($this->choiceIds($chapter?->content_blocks) as $id) {
            $allowed[$id] = true;
        }

        foreach ($this->choiceIds($blocks) as $id) {
            if (!isset($allowed[$id])) {
                throw ValidationException::withMessages([
                    'blocks' => __('story::validation.chapter.choice.foreign_target'),
                ]);
            }
        }
    }

    /**
     * @return list<int>
     */
    private function choiceIds(mixed $blocks): array
    {
        if (!is_array($blocks)) {
            return [];
        }

        $ids = [];
        foreach ($blocks as $block) {
            if (!is_array($block) || ($block['type'] ?? null) !== 'chapter-choice') {
                continue;
            }
            foreach ((array) ($block['choices'] ?? []) as $choice) {
                if (is_array($choice) && is_numeric($choice['chapter_id'] ?? null)) {
                    $ids[] = (int) $choice['chapter_id'];
                }
            }
        }

        return $ids;
    }
}
