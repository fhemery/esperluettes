<?php

namespace App\Domains\Announcement\Services;

use App\Domains\Announcement\Models\Announcement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Cache;
use function is_string;

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
        $basePath = 'announcements/' . date('Y/m');

        // Resolve source path, filename and extension depending on input type
        if ($file instanceof UploadedFile) {
            $sourcePath = $file->getRealPath();
            $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg');
            $filename = pathinfo($file->hashName(), PATHINFO_FILENAME);

            // Save original
            $originalPath = "$basePath/{$filename}.{$ext}";
            Storage::disk($disk)->putFileAs($basePath, $file, "{$filename}.{$ext}");
        } elseif (is_string($file)) {
            // $file is a path relative to the configured disk (e.g. tmp/announcements/xyz.jpg)
            $sourcePath = Storage::disk($disk)->path($file);
            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg');
            $filename = pathinfo(basename($sourcePath), PATHINFO_FILENAME);

            // Copy original from tmp into final location (leave tmp intact)
            $originalPath = "$basePath/{$filename}.{$ext}";
            if (!Storage::disk($disk)->exists($originalPath)) {
                Storage::disk($disk)->copy($file, $originalPath);
            }
        } else {
            return null;
        }

        // Generate 400w and 800w + webp
        $this->generateVariant($disk, $basePath, $filename, $sourcePath, 400);
        $this->generateVariant($disk, $basePath, $filename, $sourcePath, 800);

        return $originalPath;
    }

    protected function generateVariant(string $disk, string $basePath, string $filename, string $sourcePath, int $width): void
    {
        $image = Image::read($sourcePath)->scale(width: $width);

        // Save jpeg/png based on original pipeline
        $variantPath = sprintf('%s/%s-%dw.jpg', $basePath, $filename, $width);
        Storage::disk($disk)->put($variantPath, (string) $image->encodeByExtension('jpg', quality: 82));

        // Save webp
        $webpPath = sprintf('%s/%s-%dw.webp', $basePath, $filename, $width);
        Storage::disk($disk)->put($webpPath, (string) $image->encodeByExtension('webp', quality: 82));
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
        // Original
        if (Storage::disk($disk)->exists($headerImagePath)) {
            Storage::disk($disk)->delete($headerImagePath);
        }

        // Variants based on conventional names
        $dir = pathinfo($headerImagePath, PATHINFO_DIRNAME);
        $name = pathinfo($headerImagePath, PATHINFO_FILENAME);
        $variants = [
            "$dir/{$name}-400w.jpg",
            "$dir/{$name}-800w.jpg",
            "$dir/{$name}-400w.webp",
            "$dir/{$name}-800w.webp",
        ];
        foreach ($variants as $v) {
            if (Storage::disk($disk)->exists($v)) {
                Storage::disk($disk)->delete($v);
            }
        }
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
