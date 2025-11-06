<?php

namespace App\Domains\Story\Private\Repositories;

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Support\GetStoryOptions;
use App\Domains\Story\Private\Support\StoryFilterAndPagination;
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
        $query = Story::query()
            ->when($options->includeAuthors, fn($q) => $q->with('authors'))
            ->when($options->includeGenreIds, fn($q) => $q->with('genres:id'))
            ->when($options->includeTriggerWarningIds, fn($q) => $q->with('triggerWarnings:id'));
           
        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
        return $paginator;
    }

    /**
     * Return a paginator of stories for card display with filters applied.
     */
    public function searchStoriesForCardDisplay(StoryFilterAndPagination $filter, ?int $viewerId = null): LengthAwarePaginator
    {
        $query = $this->buildCardListingQuery($filter, $viewerId);
        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
        return $paginator;
    }

    private function selectFields(GetStoryOptions $options): Builder
    {
        $query = Story::query()
            ->when($options->includeAuthors, fn($q) => $q->with('authors'))
            ->when($options->includeCollaborators, fn($q) => $q->with('collaborators'))
            ->when($options->includeGenreIds, fn($q) => $q->with('genres:id'))
            ->when($options->includeTriggerWarningIds, fn($q) => $q->with('triggerWarnings:id'))
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
     * Build the base query used for listing story cards with filters applied.
     * This centralizes eager-loading and aggregations so card displays stay consistent.
     */
    private function buildCardListingQuery(StoryFilterAndPagination $filter, ?int $viewerId = null): Builder
    {
        $query = $this->selectFields(GetStoryOptions::ForCardDisplay());

        // Only require a published chapter for general listings; profile owner views can include drafts/no chapters
        if ($filter->requirePublishedChapter) {
            $query->whereNotNull('last_chapter_published_at');
        }

        // Order: newest publication first, then creation date; NULL last_chapter_published_at naturally sorts last on DESC
        $query->orderByDesc('last_chapter_published_at')
            ->orderByDesc('created_at');

        // Filter by author user ID (prefer authorId)
        $authorId = $filter->authorId;
        if ($authorId !== null) {
            $query->whereHas('authors', function ($q) use ($authorId) {
                $q->where('user_id', $authorId);
            });
        }

        // Filter by Type if provided
        if ($filter->typeId !== null) {
            $query->where('story_ref_type_id', $filter->typeId);
        }

        // Filter by Audience if provided (multi-select)
        if (!empty($filter->audienceIds)) {
            $query->whereIn('story_ref_audience_id', $filter->audienceIds);
        }

        // Filter by Genres (AND semantics: story must have all selected genre IDs)
        if (!empty($filter->genreIds)) {
            foreach ($filter->genreIds as $gid) {
                $query->whereHas('genres', function ($q) use ($gid) {
                    $q->where('story_ref_genres.id', $gid);
                });
            }
        }

        // Exclude stories that have ANY of the selected trigger warnings (OR semantics)
        if (!empty($filter->excludeTriggerWarningIds)) {
            $ids = $filter->excludeTriggerWarningIds;
            $query->whereDoesntHave('triggerWarnings', function ($q) use ($ids) {
                $q->whereIn('story_ref_trigger_warnings.id', $ids);
            });
        }

        // Filter only explicit No-TW stories if requested
        if ($filter->noTwOnly === true) {
            $query->where('tw_disclosure', Story::TW_NO_TW);
        }

        // Visibilities already normalized in DTO
        $visibilities = $filter->visibilities;

        $pubCom = array_values(array_intersect($visibilities, [Story::VIS_PUBLIC, Story::VIS_COMMUNITY]));
        $includePrivate = in_array(Story::VIS_PRIVATE, $visibilities, true);

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

        return $query;
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
