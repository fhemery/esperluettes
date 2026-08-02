<?php

namespace App\Domains\StaticPage\Private\Services;

use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\Shared\Support\HtmlLinkUtils;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StaticPage\Public\Events\StaticPagePublished;
use App\Domains\StaticPage\Public\Events\StaticPageUnpublished;
use App\Domains\StaticPage\Public\Events\StaticPageDeleted;

class StaticPageService
{
    private const SCOPE = 'static-pages';

    public function __construct(
        private readonly EventBus $eventBus,
        private readonly EditorPublicApi $editor,
        private readonly MediaPublicApi $media,
    ) {}

    public const CACHE_KEY_SLUG_MAP = 'static_pages:slug_map';

    /**
     * Resolve the persisted content fields from the submitted payload.
     *
     * Simple mode: `content` = sanitized author HTML, `content_blocks` = null.
     * Advanced mode: build the normalized block list (store new image uploads,
     * reuse existing paths, drop empties, sanitize text), persist it in
     * `content_blocks`, and render it into `content` as the display cache.
     *
     * @param array<string,mixed> $data
     * @return array{content:string, content_blocks:?array<int,array<string,mixed>>}
     */
    private function resolveContent(array $data): array
    {
        if (($data['mode'] ?? 'simple') !== 'advanced') {
            return [
                'content' => $this->sanitizeContent($data['content'] ?? ''),
                'content_blocks' => null,
            ];
        }

        $order = array_values(array_filter(explode(',', (string) ($data['blocks_order'] ?? '')), fn ($u) => $u !== ''));
        $raw = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];

        $blocks = [];
        foreach ($order as $uid) {
            $b = $raw[$uid] ?? null;
            if (!is_array($b)) {
                continue;
            }
            $type = $b['type'] ?? null;

            if ($type === 'text') {
                $html = $this->editor->sanitizeText((string) ($b['html'] ?? ''));
                if (trim(strip_tags($html)) === '') {
                    continue; // drop empty text block
                }
                $blocks[] = ['type' => 'text', 'html' => $html];
            } elseif ($type === 'image') {
                $keep = !empty($b['keep_original']);
                $file = $b['file'] ?? null;
                if ($file instanceof UploadedFile) {
                    // "keep original" stores the file without generating variants.
                    $path = $this->media->store(self::SCOPE, $file, $keep ? [] : [400, 800]);
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
                $alt = trim((string) ($b['alt'] ?? ''));
                if ($alt === '') {
                    throw ValidationException::withMessages([
                        'blocks' => __('static::admin.validation.image_alt_required'),
                    ]);
                }
                $block = ['type' => 'image', 'path' => $path, 'alt' => $alt];
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
                'blocks' => __('static::admin.validation.blocks_required'),
            ]);
        }

        return [
            'content' => $this->editor->render($blocks),
            'content_blocks' => $blocks,
        ];
    }

    public function create(array $data): StaticPage
    {
        $resolved = $this->resolveContent($data);
        $data['content'] = $resolved['content'];
        $data['content_blocks'] = $resolved['content_blocks'];
        unset($data['blocks'], $data['blocks_order'], $data['mode']);

        $data['header_image_path'] = $this->resolveHeaderImage($data);
        unset($data['header_image']);

        $data['created_by'] = Auth::id();

        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return StaticPage::create($data);
    }

    public function update(StaticPage $page, array $data): StaticPage
    {
        $resolved = $this->resolveContent($data);
        $data['content'] = $resolved['content'];
        $data['content_blocks'] = $resolved['content_blocks'];
        unset($data['blocks'], $data['blocks_order'], $data['mode']);

        $data['header_image_path'] = $this->resolveHeaderImage($data);
        unset($data['header_image']);

        $page->update($data);
        $this->rebuildSlugMapCache();

        return $page;
    }

    public function delete(StaticPage $page): void
    {
        $pageId = $page->id;
        $slug = $page->slug;
        $title = $page->title;

        $page->delete();

        $this->rebuildSlugMapCache();

        $this->eventBus->emit(new StaticPageDeleted(
            pageId: (int) $pageId,
            slug: (string) $slug,
            title: (string) $title,
        ));
    }

    public function sanitizeContent(string $html): string
    {
        $clean = Purifier::clean($html, 'admin-content');
        return HtmlLinkUtils::addTargetBlankToExternalLinks($clean);
    }

    /**
     * Resolve header_image_path from the media-image-field payload.
     * A new upload is stored; otherwise the (possibly reused or kept) path is
     * used; empty means the image was removed. Files are never deleted here —
     * the Media GC reclaims any path no page references anymore.
     *
     * @param array<string,mixed> $data validated request data
     */
    private function resolveHeaderImage(array $data): ?string
    {
        $file = $data['header_image']['file'] ?? null;

        return $file instanceof UploadedFile
            ? $this->processHeaderImage($file)
            : (($data['header_image']['path'] ?? null) ?: null);
    }

    public function processHeaderImage(?UploadedFile $file): ?string
    {
        return $file ? $this->media->store(self::SCOPE, $file) : null;
    }

    public function publish(StaticPage $page): StaticPage
    {
        $page->status = 'published';
        if (!$page->published_at) {
            $page->published_at = now();
        }
        $page->save();
        $this->rebuildSlugMapCache();
        // Emit domain event
        $this->eventBus->emit(new StaticPagePublished(
            pageId: (int) $page->id,
            slug: (string) $page->slug,
            title: (string) $page->title,
            publishedAt: optional($page->published_at)->toISOString(),
        ));
        return $page;
    }

    public function unpublish(StaticPage $page): StaticPage
    {
        $page->status = 'draft';
        $page->save();
        $this->rebuildSlugMapCache();
        // Emit domain event
        $this->eventBus->emit(new StaticPageUnpublished(
            pageId: (int) $page->id,
            slug: (string) $page->slug,
            title: (string) $page->title,
        ));
        return $page;
    }

    public function getSlugMap(): array
    {
        return Cache::remember(self::CACHE_KEY_SLUG_MAP, 3600, function () {
            return $this->buildSlugMap();
        });
    }

    public function rebuildSlugMapCache(): array
    {
        $map = $this->buildSlugMap();
        Cache::forever(self::CACHE_KEY_SLUG_MAP, $map);
        return $map;
    }

    protected function buildSlugMap(): array
    {
        // Only published pages in the public map
        return StaticPage::query()
            ->published()
            ->pluck('id', 'slug')
            ->toArray();
    }

    /**
     * Nullify created_by for all static pages authored by the given user.
     * Returns affected rows count.
     */
    public function nullifyCreator(int $userId): int
    {
        return StaticPage::query()
            ->where('created_by', $userId)
            ->update(['created_by' => null]);
    }
}
