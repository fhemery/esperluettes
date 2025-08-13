<?php

namespace App\Domains\Announcement\Observers;

use App\Domains\Announcement\Models\Announcement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnnouncementObserver
{
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
        Log::info('AnnouncementObserver@deleted', ['id' => $announcement->id]);
        Cache::forget('announcements.carousel');
    }

    public function restored(Announcement $announcement): void
    {
        Log::info('AnnouncementObserver@restored', ['id' => $announcement->id]);
        Cache::forget('announcements.carousel');
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
