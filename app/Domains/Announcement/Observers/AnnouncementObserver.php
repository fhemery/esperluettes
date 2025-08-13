<?php

namespace App\Domains\Announcement\Observers;

use App\Domains\Announcement\Models\Announcement;
use App\Domains\Shared\Services\ImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnnouncementObserver
{
    public function creating(Announcement $announcement): void
    {
        // Auto-assign display_order if pinned and order not provided
        if ($announcement->is_pinned && empty($announcement->display_order)) {
            $announcement->display_order = $this->nextDisplayOrder();
        }
    }

    public function updating(Announcement $announcement): void
    {
        // If toggling pin state
        if ($announcement->isDirty('is_pinned')) {
            $newPinned = (bool) $announcement->is_pinned;
            $oldPinned = (bool) $announcement->getOriginal('is_pinned');

            if ($newPinned && !$oldPinned) {
                // Became pinned: ensure it has an order
                if (empty($announcement->display_order)) {
                    $announcement->display_order = $this->nextDisplayOrder();
                }
            } elseif (!$newPinned && $oldPinned) {
                // Became unpinned: clear order
                $announcement->display_order = null;
            }
        } elseif ($announcement->is_pinned && empty($announcement->display_order)) {
            // Still pinned but no order set yet: assign one
            $announcement->display_order = $this->nextDisplayOrder();
        }
    }

    /**
     * Bust carousel cache when relevant fields change or records change lifecycle.
     */
    public function created(Announcement $announcement): void
    {
        Log::info('AnnouncementObserver@created', [
            'id' => $announcement->id,
            'is_pinned' => $announcement->is_pinned,
        ]);
        $this->bustIfRelevant($announcement, true);
    }

    public function updated(Announcement $announcement): void
    {
        Log::info('AnnouncementObserver@updated', [
            'id' => $announcement->id,
            'changes' => $announcement->getChanges(),
            'isDirty' => $announcement->isDirty(),
            'wasChanged' => $announcement->wasChanged(),
        ]);
        $this->bustIfRelevant($announcement);
    }

    public function deleted(Announcement $announcement): void
    {
        Log::info('AnnouncementObserver@deleted', [
            'id' => $announcement->id,
            'header_image_path' => $announcement->header_image_path,
        ]);
        // Delete header image and its variants if present
        if (!empty($announcement->header_image_path)) {
            app(ImageService::class)->deleteWithVariants('public', $announcement->header_image_path);
        }
        Cache::forget('announcements.carousel');
    }

    public function restored(Announcement $announcement): void
    {
        Log::info('AnnouncementObserver@restored', ['id' => $announcement->id]);
        Cache::forget('announcements.carousel');
    }

    protected function nextDisplayOrder(): int
    {
        $max = Announcement::query()->where('is_pinned', true)->max('display_order');
        return is_null($max) ? 1 : ((int) $max + 1);
    }

    protected function bustIfRelevant(Announcement $announcement, bool $onCreate = false): void
    {
        // Carousel depends on: is_pinned, display_order, status, published_at
        if ($onCreate || (bool) $announcement->is_pinned || $announcement->wasChanged(['is_pinned', 'display_order', 'status', 'published_at'])) {
            Log::info('AnnouncementObserver@bust', [
                'id' => $announcement->id,
                'onCreate' => $onCreate,
                'is_pinned' => $announcement->is_pinned,
                'changed' => $announcement->getChanges(),
            ]);
            Cache::forget('announcements.carousel');
        }
    }
}
