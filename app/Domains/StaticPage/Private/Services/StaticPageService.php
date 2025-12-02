<?php

namespace App\Domains\StaticPage\Private\Services;

use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\Shared\Services\ImageService;
use App\Domains\Shared\Support\HtmlLinkUtils;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;
use Mews\Purifier\Facades\Purifier;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StaticPage\Public\Events\StaticPagePublished;
use App\Domains\StaticPage\Public\Events\StaticPageUnpublished;
use App\Domains\StaticPage\Public\Events\StaticPageDeleted;

class StaticPageService
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}

    public const CACHE_KEY_SLUG_MAP = 'static_pages:slug_map';

    public function create(array $data): StaticPage
    {
        $data['content'] = $this->sanitizeContent($data['content'] ?? '');

        if (!empty($data['header_image']) && $data['header_image'] instanceof UploadedFile) {
            $data['header_image_path'] = $this->processHeaderImage($data['header_image']);
        }
        unset($data['header_image'], $data['header_image_remove']);

        $data['created_by'] = Auth::id();

        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return StaticPage::create($data);
    }

    public function update(StaticPage $page, array $data): StaticPage
    {
        $data['content'] = $this->sanitizeContent($data['content'] ?? '');

        // Handle header image
        if (!empty($data['header_image']) && $data['header_image'] instanceof UploadedFile) {
            // Delete old image if exists
            if ($page->header_image_path) {
                $this->deleteHeaderImage($page->header_image_path);
            }
            $data['header_image_path'] = $this->processHeaderImage($data['header_image']);
        } elseif (!empty($data['header_image_remove'])) {
            // Remove existing image
            if ($page->header_image_path) {
                $this->deleteHeaderImage($page->header_image_path);
            }
            $data['header_image_path'] = null;
        }
        unset($data['header_image'], $data['header_image_remove']);

        $page->update($data);
        $this->rebuildSlugMapCache();

        return $page;
    }

    public function delete(StaticPage $page): void
    {
        // Delete header image if exists
        if ($page->header_image_path) {
            $this->deleteHeaderImage($page->header_image_path);
        }

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

    public function processHeaderImage(UploadedFile|string|null $file): ?string
    {
        if (!$file) {
            return null;
        }
        $disk = 'public';
        $folder = 'static-pages/' . date('Y/m');
        return app(ImageService::class)->process($disk, $folder, $file, widths: [400, 800]);
    }

    public function deleteHeaderImage(?string $headerImagePath): void
    {
        if (!$headerImagePath) return;
        $disk = 'public';
        app(ImageService::class)->deleteWithVariants($disk, $headerImagePath);
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
