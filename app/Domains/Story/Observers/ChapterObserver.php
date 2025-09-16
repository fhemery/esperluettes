<?php

namespace App\Domains\Story\Observers;

use App\Domains\Shared\Support\WordCounter;
use App\Domains\Story\Models\Chapter;

class ChapterObserver
{
    /**
     * Handle the Chapter "saving" event.
     */
    public function saving(Chapter $chapter): void
    {
        // Recompute when content is dirty, or if word_count is null/undefined
        if ($chapter->isDirty('content') || $chapter->word_count === null) {
            $chapter->word_count = WordCounter::count((string) ($chapter->content ?? ''));
        }
    }
}
