<?php

namespace App\Domains\News\Private\Services;

use App\Domains\Comment\Public\Api\CommentMaintenancePublicApi;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Public\Events\NewsPublished;
use App\Domains\News\Public\Events\NewsUnpublished;
use App\Domains\News\Public\Notifications\NewsPublishedNotification;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Shared\Support\HtmlLinkUtils;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class NewsService
{
    /** Media scope for News images (header + content blocks). */
    private const SCOPE = 'news';

    public function __construct(
        private readonly EventBus $eventBus,
        private readonly NotificationPublicApi $notificationApi,
        private readonly EditorPublicApi $editor,
        private readonly MediaPublicApi $media,
        private readonly CommentMaintenancePublicApi $comments,
    ) {}

    public function sanitizeContent(string $html): string
    {
        // Purify HTML then add target="_blank" to external links
        $clean = Purifier::clean($html, 'admin-content');
        return HtmlLinkUtils::addTargetBlankToExternalLinks($clean) ?? $clean;
    }

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
                'blocks' => __('news::admin.validation.blocks_required'),
            ]);
        }

        return [
            'content' => $this->editor->render($blocks),
            'content_blocks' => $blocks,
        ];
    }

    /**
     * Create a new news article.
     */
    public function create(array $data): News
    {
        // Resolve content (simple HTML or advanced block list + rendered cache)
        $resolved = $this->resolveContent($data);
        $data['content'] = $resolved['content'];
        $data['content_blocks'] = $resolved['content_blocks'];
        unset($data['blocks'], $data['blocks_order'], $data['mode']);

        // Resolve header image from the Media image-field payload
        $data['header_image_path'] = $this->resolveHeaderImage($data);
        unset($data['header_image']);

        // Set creator
        $data['created_by'] = Auth::id();

        // Handle published_at if publishing
        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return News::create($data);
    }

    /**
     * Update an existing news article.
     */
    public function update(News $news, array $data): News
    {
        // Resolve content (simple HTML or advanced block list + rendered cache)
        $resolved = $this->resolveContent($data);
        $data['content'] = $resolved['content'];
        $data['content_blocks'] = $resolved['content_blocks'];
        unset($data['blocks'], $data['blocks_order'], $data['mode']);

        // Resolve header image from the Media image-field payload. Old files are
        // not deleted here — the Media GC reclaims any path no News row uses.
        $data['header_image_path'] = $this->resolveHeaderImage($data);
        unset($data['header_image']);

        // Handle published_at if transitioning to published
        if (($data['status'] ?? 'draft') === 'published' && !$news->published_at) {
            $data['published_at'] = now();
        }

        $news->update($data);

        return $news;
    }

    /**
     * Delete a news article and its associated resources.
     */
    public function delete(News $news): void
    {
        // Header/content image files are left to the Media GC once no News row
        // references them.

        // Bust cache if it was pinned
        if ($news->is_pinned) {
            $this->bustCarouselCache();
        }

        // Purge the article's comment thread (hard delete via maintenance API)
        // before the parent row goes away, so no orphan comment can remain.
        $this->comments->deleteFor('news', (int) $news->id);

        $news->delete();
    }

    /**
     * Resolve the header image path from the Media image-field payload.
     * New upload → stored via Media; otherwise the reused/kept path (or null).
     *
     * @param array<string,mixed> $data
     */
    private function resolveHeaderImage(array $data): ?string
    {
        $field = $data['header_image'] ?? null;
        if (!is_array($field)) {
            return null;
        }
        $file = $field['file'] ?? null;
        if ($file instanceof UploadedFile) {
            return $this->media->store(self::SCOPE, $file);
        }
        return !empty($field['path']) ? (string) $field['path'] : null;
    }

    public function publish(News $news): News
    {
        $news->status = 'published';
        if (!$news->published_at) {
            $news->published_at = now();
        }
        $news->save();
        $this->bustCarouselCache();

        // Emit domain event
        $this->eventBus->emit(new NewsPublished(
            newsId: (int) $news->id,
            slug: (string) $news->slug,
            title: (string) $news->title,
            publishedAt: optional($news->published_at)->toISOString(),
        ));

        // Broadcast notification to all users (system notification)
        $this->notificationApi->createBroadcastNotification(
            new NewsPublishedNotification(
                newsTitle: (string) $news->title,
                newsSlug: (string) $news->slug,
            ),
            sourceUserId: null, // System notification
        );

        return $news;
    }

    public function unpublish(News $news): News
    {
        $news->status = 'draft';
        $news->save();
        $this->bustCarouselCache();
        // Emit domain event
        $this->eventBus->emit(new NewsUnpublished(
            newsId: (int) $news->id,
            slug: (string) $news->slug,
            title: (string) $news->title,
        ));
        return $news;
    }

    public function pin(News $news, int $order): News
    {
        $news->is_pinned = true;
        $news->display_order = $order;
        $news->save();
        $this->bustCarouselCache();
        return $news;
    }

    public function unpin(News $news): News
    {
        $news->is_pinned = false;
        $news->display_order = null;
        $news->save();
        $this->bustCarouselCache();
        return $news;
    }

    public function bustCarouselCache(): void
    {
        Cache::forget('news.carousel');
    }

    public function getPinnedForCarousel()
    {
        return Cache::remember('news.carousel', 300, function () {
            return News::query()
                ->pinned()
                ->published()
                ->orderBy('display_order', 'asc')
                ->orderByDesc('published_at')
                ->get();
        });
    }

    /**
     * Nullify created_by for all news authored by the given user.
     * Returns affected rows count.
     */
    public function nullifyCreator(int $userId): int
    {
        return News::query()
            ->where('created_by', $userId)
            ->update(['created_by' => null]);
    }
}
