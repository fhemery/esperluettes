<?php

namespace App\Domains\Story\Private\Repositories;

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Support\GetStoryOptions;

final class StoryRepository
{
    public function getStorySummaryById(int $storyId, GetStoryOptions $options = new GetStoryOptions()): ?Story
    {
        $query = Story::query()
            ->when($options->includeAuthors, fn($q) => $q->with('authors'))
            ->when($options->includeGenreIds, fn($q) => $q->with('genres:id'))
            ->when($options->includeTriggerWarningIds, fn($q) => $q->with('triggerWarnings:id'))
            ->when($options->includeChapters, fn($q) => $q->with('chapters'));

        // Aggregate metrics for each story (avoid N+1):
        // - published_chapters_count: count of published chapters
        // - published_words_total: sum of word_count across published chapters
        if ($options->includePublishedChaptersCount) {
            $query->withCount([
                'chapters as published_chapters_count' => function ($q) {
                    $q->where('status', Chapter::STATUS_PUBLISHED);
                },
            ]);
        }
        if ($options->includeWordCount) {
            $query->withSum([
                'chapters as published_words_total' => function ($q) {
                    $q->where('status', Chapter::STATUS_PUBLISHED);
                },
            ], 'word_count');
        }

        return $query->find($storyId);
    }
}
