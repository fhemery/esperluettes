<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Story\Private\Http\Requests\StoryRequest;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\ReadingProgress;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Support\StoryFilterAndPagination;
use App\Domains\Story\Private\Support\GetStoryOptions;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Story\Public\Events\StoryCreated;
use App\Domains\Story\Public\Events\DTO\StorySnapshot;
use App\Domains\Story\Public\Events\StoryUpdated;
use App\Domains\Story\Public\Events\StoryDeleted;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;
use App\Domains\Comment\Public\Api\CommentMaintenancePublicApi;
use App\Domains\Story\Private\Models\StoryWithNextChapter;
use App\Domains\Story\Private\Repositories\StoryRepository;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class StoryService
{
    public function __construct(
        private EventBus $eventBus,
        private CommentMaintenancePublicApi $comments,
        private ProfilePublicApi $profileApi,
        private ChapterService $chapters,
        private StoryRepository $storiesRepository,
    ) {}

    /**
     * Generic listing with optional filters.
     *
     * @param StoryFilterAndPagination $filter Filters and pagination (page, perPage, visibilities, userId)
     * @param int|null $viewerId If provided, include private stories where this user is a collaborator
     */
    public function getStories(StoryFilterAndPagination $filter, ?int $viewerId = null): LengthAwarePaginator
    {
        return $this->storiesRepository->searchStoriesForCardDisplay($filter, $viewerId);
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

            // 3b) Attach trigger warnings and persist tw_disclosure as selected
            $twIds = $request->input('story_ref_trigger_warning_ids', []);
            $disclosure = $request->input('tw_disclosure'); // listed | no_tw | unspoiled
            $allowedDisclosure = Story::twDisclosureOptions();
            if (!in_array($disclosure, $allowedDisclosure, true)) {
                throw new \InvalidArgumentException('Invalid tw_disclosure value: ' . (string)$disclosure);
            }
            $twIds = is_array($twIds) ? array_values(array_unique(array_map('intval', $twIds))) : [];
            $story->tw_disclosure = $disclosure;
            $story->triggerWarnings()->sync($twIds);
            $story->save();

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
    public function getStory(string $slug, ?GetStoryOptions $options = null): ?Story
    {
        $opts = $options ?? GetStoryOptions::Full();
        $id = SlugWithId::extractId($slug);

        return $this->storiesRepository->getStoryById($id, Auth::id(), $opts);
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

            // Sync trigger warnings and tw_disclosure
            $twIds = $request->input('story_ref_trigger_warning_ids', []);
            $disclosure = $request->input('tw_disclosure');
            $allowedDisclosure = Story::twDisclosureOptions();
            if (!in_array($disclosure, $allowedDisclosure, true)) {
                throw new \InvalidArgumentException('Invalid tw_disclosure value: ' . (string)$disclosure);
            }
            $twIds = is_array($twIds) ? array_values(array_unique(array_map('intval', $twIds))) : [];
            $story->tw_disclosure = $disclosure;
            $story->triggerWarnings()->sync($twIds);

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

    public function countAuthoredStories(int $userId): int
    {
        return Story::query()->with('authors')->whereHas('authors', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();
    }

    public function getStoryByLatestAddedChapter(int $userId): ?Story
    {
        $latestChapter = Chapter::query()->with('story')->whereHas('story', function ($q) use ($userId) {
            $q->whereHas('authors', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        })->orderByDesc('last_edited_at')->first();

        if (!$latestChapter) {
            return null;
        }

        return $this->storiesRepository->getStoryById($latestChapter?->story_id, Auth::id(), GetStoryOptions::ForCardDisplay());
    }

    public function getKeepReadingContextForUser(int $userId): ?StoryWithNextChapter
    {
        // Get distinct story IDs ordered by latest activity (updated_at/read_at) to avoid duplicates
        $storyIds = ReadingProgress::query()
            ->where('user_id', $userId)
            ->selectRaw('story_id, MAX(read_at) as max_read_at')
            ->groupBy('story_id')
            ->orderByDesc('max_read_at')
            ->limit(4)
            ->pluck('story_id');

        if ($storyIds->isEmpty()) {
            return null;
        }

        foreach ($storyIds as $sid) {
            $story = $this->storiesRepository->getStoryById((int) $sid, Auth::id(), GetStoryOptions::Full());

            foreach ($story->chapters as $chapter) {
                if ($chapter->status === Chapter::STATUS_PUBLISHED) {
                    if (!$chapter->getIsRead()) {
                        return new StoryWithNextChapter($story, $chapter);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Random discover stories for the dashboard/component.
     * Excludes stories authored by the user and respects visibility rules.
     *
     * @param array<string> $visibilities Visibilities to include (e.g. [Story::VIS_PUBLIC, Story::VIS_COMMUNITY])
     * @return array<Story>
     */
    public function getRandomStories(int $userId, int $nbStories = 7, array $visibilities = [Story::VIS_PUBLIC]): array
    {
        return $this->storiesRepository->getRandomStories($userId, $nbStories, $visibilities);
    }

    /**
     * Delete all stories authored by the given user, including chapters and their comments.
     * A story qualifies if the user appears in its authors (even if there are other authors).
     */
    public function deleteStoriesByAuthor(int $userId): void
    {
        // Fetch stories where the user is among the authors
        $stories = $this->storiesRepository->findByAuthor($userId);

        foreach ($stories as $story) {
            $this->deleteStory($story);
        }
    }
}
