<?php

namespace App\Domains\Announcement\Services;

use App\Domains\Announcement\Models\Announcement;
use App\Domains\Shared\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Cache;

class AnnouncementService
{
    public function sanitizeContent(string $html): string
    {
        return Purifier::clean($html, 'announcements');
    }

    public function processHeaderImage(UploadedFile|string|null $file): ?string
    {
        if (!$file) {
            return null;
        }

        $disk = 'public';
        $folder = 'announcements/' . date('Y/m');

        // Normalize Filament temp array handled at caller; we accept UploadedFile|string here
        $imageService = app(ImageService::class);
        return $imageService->process($disk, $folder, $file, widths: [400, 800]);
    }

    public function publish(Announcement $announcement): Announcement
    {
        $announcement->status = 'published';
        if (!$announcement->published_at) {
            $announcement->published_at = now();
        }
        $announcement->save();
        $this->bustCarouselCache();
        return $announcement;
    }

    public function unpublish(Announcement $announcement): Announcement
    {
        $announcement->status = 'draft';
        $announcement->save();
        $this->bustCarouselCache();
        return $announcement;
    }

    public function pin(Announcement $announcement, int $order): Announcement
    {
        $announcement->is_pinned = true;
        $announcement->display_order = $order;
        $announcement->save();
        $this->bustCarouselCache();
        return $announcement;
    }

    public function unpin(Announcement $announcement): Announcement
    {
        $announcement->is_pinned = false;
        $announcement->display_order = null;
        $announcement->save();
        $this->bustCarouselCache();
        return $announcement;
    }

    /**
     * Delete an existing header image and its generated variants.
     */
    public function deleteHeaderImage(?string $headerImagePath): void
    {
        if (!$headerImagePath) {
            return;
        }
        $disk = 'public';
        app(ImageService::class)->deleteWithVariants($disk, $headerImagePath);
    }

    public function bustCarouselCache(): void
    {
        Cache::forget('announcements.carousel');
    }

    public function getPinnedForCarousel()
    {
        return Cache::remember('announcements.carousel', 300, function () {
            return Announcement::query()
                ->pinned()
                ->published()
                ->orderBy('display_order', 'asc')
                ->orderByDesc('published_at')
                ->get();
        });
    }
}
