<?php

namespace App\Domains\Story\Private\Observers;

use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Shared\Support\WordCounter;
use App\Domains\Shared\Support\CharacterCounter;
use App\Domains\Story\Private\Models\Chapter;

class ChapterObserver
{
    public function __construct(
        private readonly EditorPublicApi $editor,
    ) {}

    /**
     * Handle the Chapter "saving" event.
     */
    public function saving(Chapter $chapter): void
    {
        // Recompute when content or blocks are dirty, or if the counts are unset
        if ($chapter->isDirty('content') || $chapter->isDirty('content_blocks')
            || $chapter->word_count === null || $chapter->character_count === null) {
            $counted = $this->countedText($chapter);
            $chapter->word_count = WordCounter::count($counted);
            $chapter->character_count = CharacterCounter::count($counted);
        }
    }

    /**
     * Counts come from text blocks only when the chapter is in Advanced mode —
     * image alt text and captions never count (functional §4.6.1).
     */
    private function countedText(Chapter $chapter): string
    {
        $blocks = $chapter->content_blocks;
        if (is_array($blocks) && $blocks !== []) {
            return $this->editor->plainText($blocks);
        }

        return (string) ($chapter->content ?? '');
    }
}
