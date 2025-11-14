<?php

namespace App\Domains\Story\Private\Repositories;

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Support\GetStoryOptions;
use App\Domains\Story\Private\Support\StoryFilterAndPagination;
use App\Domains\Story\Public\Contracts\StoryQueryReadStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class StoryRepository
{
    public function getStoryById(int $storyId, ?int $viewerId = null, GetStoryOptions $options = new GetStoryOptions()): ?Story
    {
        $query = $this->selectFields($options);

        return $query->find($storyId);
    }

    public function setVisibility(int $storyId, string $visibility): void
    {
        Story::query()->where('id', $storyId)->update(['visibility' => $visibility]);
    }

    public function clearDescription(int $storyId): void
    {
        Story::query()->where('id', $storyId)->update(['description' => '']);
    }

    /**
     * Return a paginator of stories for card display with filters applied.
     * 
     * @param StoryFilterAndPagination $filter
     * @param GetStoryOptions $options
     * @return LengthAwarePaginator<Story>
     */
    public function searchStories(StoryFilterAndPagination $filter, GetStoryOptions $options): LengthAwarePaginator
    {
        $viewerId = Auth::id();

        $query = Story::query()
            ->when($options->includeAuthors, fn($q) => $q->with('authors'))
            ->when($options->includeGenreIds, fn($q) => $q->with('genres'))
            ->when($options->includeTriggerWarningIds, fn($q) => $q->with('triggerWarnings'))
            ->when(
                $options->includeChapters && !$options->includeReadingProgress,
                fn($q) => $q->with(['chapters' => function ($c) {
                    // Exclude author_note and content for performance consideration
                    // Important: include 'id' and 'story_id' to keep relation hydrated
                    $c->select(['id', 'story_id', 'title', 'slug', 'sort_order', 'status', 'first_published_at', 'reads_logged_count', 'word_count', 'character_count']);
                }])
            )
            ->when(
                $options->includeChapters && $options->includeReadingProgress,
                fn($q) => $q->with(['chapters' => function ($chapters) use ($viewerId) {
                    // Add an is_read count (0/1) per chapter for the current user
                    $chapters->withCount([
                        'readingProgress as is_read' => function ($rp) use ($viewerId) {
                            if ($viewerId) {
                                $rp->where('user_id', $viewerId);
                            } else {
                                // Guest: no rows -> count = 0
                                $rp->whereRaw('1 = 0');
                            }
                        }
                    ]);
                }])
            )
            ->when($options->includePublishedChaptersCount, fn($q) => $q->withCount([
                'chapters as published_chapters_count' => function ($q) {
                    $q->where('status', Chapter::STATUS_PUBLISHED);
                },
            ]))
            ->when($options->includeWordCount, fn($q) => $q->withSum([
                'chapters as published_words_total' => function ($q) {
                    $q->where('status', Chapter::STATUS_PUBLISHED);
                },
            ], 'word_count'))
            ->when($filter->onlyStoryIds, fn($q) => $q->whereIn('id', $filter->onlyStoryIds));

        // Story visibility rules
        $pubCom = array_values(array_intersect($filter->visibilities, [Story::VIS_PUBLIC, Story::VIS_COMMUNITY]));
        $includePrivate = in_array(Story::VIS_PRIVATE, $filter->visibilities, true);

        $query->where(function ($w) use ($pubCom, $includePrivate, $viewerId) {
            $addedAny = false;

            if (!empty($pubCom)) {
                $w->whereIn('visibility', $pubCom);
                $addedAny = true;
            }

            if ($includePrivate && $viewerId !== null) {
                if ($addedAny) {
                    $w->orWhere(function ($q) use ($viewerId) {
                        $q->where('visibility', Story::VIS_PRIVATE)
                            ->whereHas('collaborators', function ($c) use ($viewerId) {
                                $c->where('user_id', $viewerId);
                            });
                    });
                } else {
                    $w->where('visibility', Story::VIS_PRIVATE)
                        ->whereHas('collaborators', function ($c) use ($viewerId) {
                            $c->where('user_id', $viewerId);
                        });
                }
            }
        });

        // Filter by Genres (AND semantics: story must have all selected genre IDs)
        if (!empty($filter->genreIds)) {
            foreach ($filter->genreIds as $gid) {
                $query->whereHas('genres', function ($q) use ($gid) {
                    $q->where('story_ref_genre_id', (int) $gid);
                });
            }
        }

        // Exclude stories that have ANY of the selected trigger warnings (OR semantics)
        if ($filter->noTwOnly) {
            $query->where('tw_disclosure', Story::TW_NO_TW);
        } else if (!empty($filter->excludeTriggerWarningIds)) {
            $ids = $filter->excludeTriggerWarningIds;
            $query->whereDoesntHave('triggerWarnings', function ($q) use ($ids) {
                $q->whereIn('story_ref_trigger_warning_id', $ids);
            });
        }

        // Filter by Story Types (OR semantics: story must have at least one selected story type ID)
        if (!empty($filter->typeIds)) {
            $query->whereIn('story_ref_type_id', $filter->typeIds);
        }

        // Filter by Audience (OR semantics: story must have at least one selected audience ID)
        if (!empty($filter->audienceIds)) {
            $query->whereIn('story_ref_audience_id', $filter->audienceIds);
        }

        // Filter by Author (OR semantics: story must have at least one selected author ID)
        if (!empty($filter->authorIds)) {
            $query->whereHas('authors', function ($q) use ($filter) {
                $q->whereIn('user_id', $filter->authorIds);
            });
        }

        // Filter stories that have no published chapter
        if ($filter->requirePublishedChapter) {
            $query->whereHas('chapters', function ($q) {
                $q->where('status', Chapter::STATUS_PUBLISHED);
            });
        }

        // Read status filtering (based on published chapters only)
        if ($filter->readStatus === StoryQueryReadStatus::UnreadOnly) {
            $query->whereHas('chapters', function ($q) use ($viewerId) {
                $q->where('status', Chapter::STATUS_PUBLISHED)
                    ->whereDoesntHave('readingProgress', function ($rp) use ($viewerId) {
                        if ($viewerId) {
                            $rp->where('user_id', $viewerId);
                        } else {
                            // Guest: no reading progress -> all chapters are considered unread
                            $rp->whereRaw('1 = 0');
                        }
                    });
            });
        } elseif ($filter->readStatus === StoryQueryReadStatus::ReadOnly) {
            if ($viewerId) {
                // Consider a story READ if it has NO unread published chapters for the viewer
                $query->whereDoesntHave('chapters', function ($q) use ($viewerId) {
                    $q->where('status', Chapter::STATUS_PUBLISHED)
                        ->whereDoesntHave('readingProgress', function ($rp) use ($viewerId) {
                            // If the chapter lacks a reading progress row for this viewer, it's unread
                            $rp->where('user_id', $viewerId);
                        });
                });
            } else {
                // Guest cannot have read chapters -> return none for ReadOnly
                $query->whereRaw('1 = 0');
            }
        }
        $query->orderByRaw('COALESCE(last_chapter_published_at, created_at) DESC, created_at DESC');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
        return $paginator;
    }

    private function selectFields(GetStoryOptions $options): Builder
    {
        $query = Story::query()
            ->when($options->includeAuthors, fn($q) => $q->with('authors'))
            ->when($options->includeCollaborators, fn($q) => $q->with('collaborators'))
            ->when($options->includeGenreIds, fn($q) => $q->with('genres'))
            ->when($options->includeTriggerWarningIds, fn($q) => $q->with('triggerWarnings'))
            ->when($options->includeChapters && !$options->includeReadingProgress, fn($q) => $q->with('chapters'))
            ->when($options->includeChapters && $options->includeReadingProgress, function ($q) {
                $userId = Auth::id();
                $q->with(['chapters' => function ($chapters) use ($userId) {
                    // Add an is_read count (0/1) per chapter for the current user
                    $chapters->withCount([
                        'readingProgress as is_read' => function ($rp) use ($userId) {
                            if ($userId) {
                                $rp->where('user_id', $userId);
                            } else {
                                // Guest: no rows -> count = 0
                                $rp->whereRaw('1 = 0');
                            }
                        }
                    ]);
                }]);
            });

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

        return $query;
    }

    /**
     * Return all stories authored by the given user (appears in authors relation).
     *
     * @return Collection<int, Story>
     */
    public function findByAuthor(int $userId): Collection
    {
        return Story::query()
            ->whereHas('authors', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->get();
    }

    /**
     * Fetch authored stories for a user, optionally excluding co-authored ones.
     * Ordered by updated_at DESC, then id DESC.
     *
     * @return Collection<int, Story>
     */
    public function findAuthoredForUserOrdered(int $userId, bool $excludeCoauthored = false): Collection
    {
        $query = Story::query()
            ->whereHas('authors', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($excludeCoauthored) {
            $query->whereDoesntHave('authors', function ($q) use ($userId) {
                $q->where('user_id', '!=', $userId);
            });
        }

        return $query->get();
    }


    /**
     * Fetch random stories for discovery, excluding stories authored by the given viewer.
     * Respects visibility: public/community; includes private only if the viewer is a collaborator.
     * Eager-loads fields suitable for card display.
     * 
     * @return array<Story>
     */
    public function getRandomStories(int $viewerId, int $nbStories = 7, array $visibilities = [Story::VIS_PUBLIC]): array
    {
        $query = $this->selectFields(GetStoryOptions::ForCardDisplay());

        // Exclude stories authored by the viewer
        $query->whereDoesntHave('authors', function ($q) use ($viewerId) {
            $q->where('user_id', $viewerId);
        });

        // Must have at least one PUBLISHED chapter
        $query->whereHas('chapters', function ($q) {
            $q->where('status', Chapter::STATUS_PUBLISHED);
        });

        // Visibility: allow public and community only
        $query->whereIn('visibility', $visibilities);

        return $query->inRandomOrder()->limit($nbStories)->get()->all();
    }
}
