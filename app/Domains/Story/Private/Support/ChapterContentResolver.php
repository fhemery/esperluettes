<?php

declare(strict_types=1);

namespace App\Domains\Story\Private\Support;

use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Shared\Support\HtmlLinkUtils;
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
 */
class ChapterContentResolver
{
    public function __construct(
        private readonly EditorPublicApi $editor,
        private readonly MediaPublicApi $media,
    ) {}

    /**
     * @param array<string,mixed> $data
     * @return array{content: string, content_blocks: ?array<int,array<string,mixed>>}
     */
    public function resolve(array $data, int $actingUserId): array
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
            }
        }

        if ($blocks === []) {
            throw ValidationException::withMessages([
                'blocks' => __('story::validation.chapter.blocks.required'),
            ]);
        }

        return [
            'content' => $this->editor->render($blocks, 'multiedit-narrative'),
            'content_blocks' => $blocks,
        ];
    }
}
