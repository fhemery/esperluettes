<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Support\StoryFilterAndPagination;
use App\Domains\Story\Support\GetStoryOptions;
use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Story\Events\StoryCreated;
use App\Domains\Story\Events\DTO\StorySnapshot;
use App\Domains\Story\Events\StoryUpdated;
use App\Domains\Story\Events\StoryDeleted;
use App\Domains\Story\Events\DTO\ChapterSnapshot;
use App\Domains\Comment\PublicApi\CommentMaintenancePublicApi;
use App\Domains\Story\Events\StoryVisibilityChanged;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StoryService
{
    public function __construct(
        private EventBus $eventBus,
        private CommentMaintenancePublicApi $comments,
    ) {
    }

    /**
     * Generic listing with optional filters.
     *
     * @param StoryFilterAndPagination $filter Filters and pagination (page, perPage, visibilities, userId)
     * @param int|null $viewerId If provided, include private stories where this user is a collaborator
     */
    public function getStories(StoryFilterAndPagination $filter, ?int $viewerId = null): LengthAwarePaginator
    {
        $query = Story::query()
            ->with(['authors', 'collaborators', 'genres:id', 'triggerWarnings:id']);

        // Aggregate metrics for each story (avoid N+1):
        // - published_chapters_count: count of published chapters
        // - published_words_total: sum of word_count across published chapters
        $query->withCount([
            'chapters as published_chapters_count' => function ($q) {
                $q->where('status', Chapter::STATUS_PUBLISHED);
            },
        ])->withSum([
            'chapters as published_words_total' => function ($q) {
                $q->where('status', Chapter::STATUS_PUBLISHED);
            },
        ], 'word_count');

        // Only require a published chapter for general listings; profile owner views can include drafts/no chapters
        if ($filter->requirePublishedChapter) {
            $query->whereNotNull('last_chapter_published_at');
        }

        // Order: newest publication first, then creation date; NULL last_chapter_published_at naturally sorts last on DESC
        $query->orderByDesc('last_chapter_published_at')
              ->orderByDesc('created_at');

        if ($filter->userId !== null) {
            $query->whereHas('authors', function ($q) use ($filter) {
                $q->where('user_id', $filter->userId);
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

        /** @var LengthAwarePaginator $stories */
        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    public function createStory(StoryRequest $request, int $userId): Story
    {
        return DB::transaction(function () use ($request, $userId) {
            // 1) Create story with temporary slug
            $title = (string) $request->input('title');
            $description = (string) $request->input('description');
            $visibility = (string) $request->input('visibility');
            $slugBase = Story::generateSlugBase($title);

            $story = new Story([
                'created_by_user_id' => $userId,
                'title' => $title,
                'slug' => $slugBase, // temporary
                'description' => $description,
                'visibility' => $visibility,
            ]);
            // Set mandatory reference fields
            $story->story_ref_type_id = (int) $request->input('story_ref_type_id');
            $story->story_ref_audience_id = (int) $request->input('story_ref_audience_id');
            $story->story_ref_copyright_id = (int)$request->input('story_ref_copyright_id');
            $statusId = $request->input('story_ref_status_id');
            $story->story_ref_status_id = $statusId !== null ? (int)$statusId : null;
            $feedbackId = $request->input('story_ref_feedback_id');
            $story->story_ref_feedback_id = $feedbackId !== null ? (int)$feedbackId : null;
            $story->save();

            // 2) Update slug with id suffix
            $story->slug = SlugWithId::build($slugBase, $story->id);
            $story->save();

            // 3) Attach genres (1..3)
            $genreIds = $request->input('story_ref_genre_ids', []);
            if (is_array($genreIds)) {
                $ids = array_values(array_unique(array_map('intval', $genreIds)));
                $story->genres()->sync($ids);
            }

            // 3b) Attach trigger warnings (optional)
            $twIds = $request->input('story_ref_trigger_warning_ids', []);
            if (is_array($twIds)) {
                $ids = array_values(array_unique(array_map('intval', $twIds)));
                $story->triggerWarnings()->sync($ids);
            }

            // 4) Seed collaborator row for creator
            DB::table('story_collaborators')->insert([
                'story_id' => $story->id,
                'user_id' => $userId,
                'role' => 'author',
                'invited_by_user_id' => $userId,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            // 5) Emit Story.Created event with full snapshot
            $snapshot = StorySnapshot::fromModel($story, $userId);
            $this->eventBus->emit(new StoryCreated($snapshot));

            return $story;
        });
    }

    /**
     * Fetch a story by slug (or slug-with-id).
     * Eager-loading is controlled via GetStoryOptions to keep payload lean.
     */
    public function getStory(string $slug, ?GetStoryOptions $options = null): Story
    {
        $opts = $options ?? new GetStoryOptions();
        $id = SlugWithId::extractId($slug);

        $query = Story::query();

        $with = [];
        if ($opts->includeAuthors) {
            $with[] = 'authors';
        }
        if ($opts->includeGenreIds) {
            $with[] = 'genres:id';
        }
        if ($opts->includeTriggerWarningIds) {
            $with[] = 'triggerWarnings:id';
        }
        if ($opts->includeChapters) {
            $with['chapters'] = function ($q) {
                $q->orderBy('sort_order', 'asc')
                  ->select(['id', 'story_id', 'title', 'slug', 'status', 'sort_order']);
            };
        }
        if (!empty($with)) {
            $query->with($with);
        }

        return $id
            ? $query->findOrFail($id)
            : $query->where('slug', $slug)->firstOrFail();
    }

    /**
     * Update a story's core fields and relations, emitting Story.Updated with before/after snapshots.
     */
    public function updateStory(StoryRequest $request, Story $story): Story
    {
        return DB::transaction(function () use ($request, $story) {
            // Snapshot before
            $before = StorySnapshot::fromModel($story, (int) $story->created_by_user_id);

            $oldTitle = (string) $story->title;

            // Core fields
            $story->title = (string)$request->input('title');
            $story->description = (string)$request->input('description');
            $story->visibility = (string)$request->input('visibility');
            $story->story_ref_type_id = (int)$request->input('story_ref_type_id');
            $story->story_ref_audience_id = (int)$request->input('story_ref_audience_id');
            $story->story_ref_copyright_id = (int)$request->input('story_ref_copyright_id');
            $statusId = $request->input('story_ref_status_id');
            $story->story_ref_status_id = $statusId !== null ? (int)$statusId : null;
            $feedbackId = $request->input('story_ref_feedback_id');
            $story->story_ref_feedback_id = $feedbackId !== null ? (int)$feedbackId : null;

            // Sync genres (1..3)
            $genreIds = $request->input('story_ref_genre_ids', []);
            if (is_array($genreIds)) {
                $ids = array_values(array_unique(array_map('intval', $genreIds)));
                $story->genres()->sync($ids);
            }

            // Sync trigger warnings (optional)
            $twIds = $request->input('story_ref_trigger_warning_ids', []);
            if (is_array($twIds)) {
                $ids = array_values(array_unique(array_map('intval', $twIds)));
                $story->triggerWarnings()->sync($ids);
            }

            // If title changed, regenerate slug base but keep -id suffix
            if ($story->title !== $oldTitle) {
                $slugBase = Story::generateSlugBase($story->title);
                $story->slug = SlugWithId::build($slugBase, $story->id);
            }

            $story->save();

            // Snapshot after & emit
            $after = StorySnapshot::fromModel($story, (int) $story->created_by_user_id);
            $this->eventBus->emit(new StoryUpdated($before, $after));

            // Emit Story.VisibilityChanged if visibility changed
            if ($before->visibility !== $story->visibility) {
                $this->eventBus->emit(new StoryVisibilityChanged(
                    storyId: (int) $story->id,
                    title: (string) $story->title,
                    oldVisibility: (string) $before->visibility,
                    newVisibility: (string) $story->visibility,
                ));
            }

            return $story;
        });
    }

    /**
     * Hard delete a story and let DB cascades clean related records.
     */
    public function deleteStory(Story $story): void
    {
        DB::transaction(function () use ($story) {
            // Build snapshots before deletion
            $before = StorySnapshot::fromModel($story, (int) $story->created_by_user_id);
            $chapters = $story->chapters()->orderBy('sort_order')->get();
            $chapterSnaps = $chapters->map(fn($c) => ChapterSnapshot::fromModel($c))->all();

            // Purge comments for all chapters and the story itself (if any)
            $chapterIds = $chapters->pluck('id')->all();
            foreach ($chapterIds as $cid) {
                $this->comments->deleteFor('chapter', (int) $cid);
            }

            // Perform deletion (DB cascades will remove related rows)
            $story->delete();

            // Emit deletion event
            $this->eventBus->emit(new StoryDeleted($before, $chapterSnaps));
        });
    }
}
