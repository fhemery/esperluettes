<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Shared\Support\SparseReorder;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Http\Requests\ChapterRequest;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\ChapterPublished;
use App\Domains\Story\Public\Events\ChapterUnpublished;
use App\Domains\Story\Public\Events\ChapterUnpublishedByModeration;
use App\Domains\Story\Public\Events\ChapterContentModerated;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;
use App\Domains\Story\Public\Notifications\CoAuthorChapterCreatedNotification;
use App\Domains\Story\Public\Notifications\CoAuthorChapterUpdatedNotification;
use App\Domains\Story\Public\Notifications\CoAuthorChapterDeletedNotification;
use App\Domains\Comment\Public\Api\CommentMaintenancePublicApi;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Story\Private\Services\ChapterCreditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChapterService
{
    public function __construct(
        private readonly CommentMaintenancePublicApi $comments,
        private readonly EventBus $eventBus,
        private readonly ChapterCreditService $credits,
        private readonly NotificationPublicApi $notifications,
        private readonly ProfilePublicApi $profiles,
    ) {}

    public function createChapter(Story $story, ChapterRequest $request, int $userId): Chapter
    {
        $title = (string) $request->input('title');
        $authorNoteHtml = $request->input('author_note'); 
        $contentHtml = (string) $request->input('content');
        $published = (bool)($request->boolean('published', false));

        // Enforce chapter credits: must have at least 1 available to create
        if ($this->credits->availableForUser($userId) <= 0) {
            throw new \Illuminate\Auth\Access\AuthorizationException('No chapter credits left. Comment other chapters to earn more.');
        }
        
        return DB::transaction(function () use ($story, $title, $authorNoteHtml, $contentHtml, $published, $userId) {
            // compute sparse sort order
            $maxOrder = (int) (Chapter::where('story_id', $story->id)->max('sort_order') ?? 0);
            $sortOrder = $maxOrder + 100;

            // temporary slug base (without id)
            $slugBase = Str::slug($title);

            $chapter = new Chapter();
            $chapter->story_id = $story->id;
            $chapter->title = $title;
            // Use a temporary unique slug to satisfy the unique index on first insert,
            // then replace with the canonical slug including the -id suffix after we have the id.
            // Keep it under 255 chars to avoid DB errors.
            $tmpSlug = Str::limit($slugBase, 240, '') . '-' . Str::lower(Str::random(12));
            $chapter->slug = $tmpSlug; // will update with id suffix after save
            $chapter->author_note = $authorNoteHtml;
            $chapter->content = $contentHtml;
            $chapter->sort_order = $sortOrder;
            $chapter->status = $published ? Chapter::STATUS_PUBLISHED : Chapter::STATUS_NOT_PUBLISHED;

            if ($published) {
                $chapter->first_published_at = now();
            }

            // Any authoring action counts as an edit
            $chapter->last_edited_at = now();
            $chapter->save();

            // update slug with -id suffix and ensure global uniqueness
            $chapter->slug = SlugWithId::build($slugBase, $chapter->id);
            $chapter->save();

            // Update story.last_chapter_published_at only on first publish
            if ($published && $chapter->first_published_at) {
                $this->updateStoryLastPublished($story);
            }

            // Emit Chapter.Created with a lightweight snapshot
            $this->eventBus->emit(new ChapterCreated(
                storyId: (int) $story->id,
                chapter: ChapterSnapshot::fromModel($chapter),
            ));

            // Record spend after successful creation
            $this->credits->spendOne((int)$userId);

            // Notify co-authors about the new chapter
            $this->notifyCoAuthorsOfChapterChange($story, $userId, 'created', $title, $chapter->slug);

            return $chapter;
        });
    }

    /**
     * Lightweight accessor to retrieve a Chapter by its id.
     */
    public function getChapterById(int $chapterId): ?Chapter
    {
        return Chapter::query()->find($chapterId);
    }


    /**
     * Empty the chapter content (set to empty string, not null) by slug.
     */
    public function emptyContentBySlug(string $slug): void
    {
        DB::transaction(function () use ($slug) {
            /** @var Chapter|null $chapter */
            $chapter = Chapter::query()->where('slug', $slug)->first();
            if (!$chapter) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Chapter not found');
            }

            $chapter->content = '';
            $chapter->save();

            $this->eventBus->emit(new ChapterContentModerated(
                storyId: (int)$chapter->story_id,
                chapterId: (int)$chapter->id,
                title: (string)$chapter->title,
            ));
        });
    }


    /**
     * Unpublish a chapter by its canonical slug. No-op if already not published.
     */
    public function unpublishBySlug(string $slug): Story
    {
        return DB::transaction(function () use ($slug) {
            /** @var Chapter|null $chapter */
            $chapter = Chapter::query()->where('slug', $slug)->first();
            if (!$chapter) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Chapter not found');
            }

            /** @var Story $story */
            $story = Story::query()->findOrFail((int)$chapter->story_id);

            $wasPublished = $chapter->status === Chapter::STATUS_PUBLISHED;
            if ($wasPublished) {
                $chapter->status = Chapter::STATUS_NOT_PUBLISHED;
                $chapter->save();

                // Emit base domain transition event
                $this->eventBus->emit(new ChapterUnpublished(
                    storyId: (int) $story->id,
                    chapter: \App\Domains\Story\Public\Events\DTO\ChapterSnapshot::fromModel($chapter),
                ));

                // Update story last published timestamp when transitions happen
                $this->updateStoryLastPublished($story);
            }

            // Emit moderation event even if already unpublished? Align with Story tests -> emit on action.
            $this->eventBus->emit(new ChapterUnpublishedByModeration(
                storyId: (int)$story->id,
                chapterId: (int)$chapter->id,
                title: (string)$chapter->title,
            ));
            return $story;
        });
    }

    /**
     * Reorder chapters of a story using SparseReorder and persist only changed rows.
     * @return array<int,int> changes map id => new sort_order
     */
    public function reorderChapters(Story $story, array $orderedIds, int $step = 100): array
    {
        return DB::transaction(function () use ($story, $orderedIds, $step) {
            $chapters = Chapter::query()
                ->where('story_id', $story->id)
                ->orderBy('sort_order')
                ->get()
                ->all();

            // Compute changes (throws if not a permutation)
            $changes = SparseReorder::computeChanges($chapters, $orderedIds, $step);

            if (!empty($changes)) {
                foreach ($changes as $id => $newOrder) {
                    Chapter::where('story_id', $story->id)
                        ->where('id', $id)
                        ->update(['sort_order' => $newOrder]);
                }
            }

            return $changes;
        });
    }

    public function updateChapter(Story $story, Chapter $chapter, ChapterRequest $request, int $userId): Chapter
    {
        $title = (string) $request->input('title');
        $authorNoteHtml = $request->input('author_note'); // purified or null
        $contentHtml = (string) $request->input('content'); // purified
        $published = (bool)($request->boolean('published', false));

        return DB::transaction(function () use ($story, $chapter, $title, $authorNoteHtml, $contentHtml, $published, $userId) {
            // Snapshot BEFORE changes
            $before = ChapterSnapshot::fromModel($chapter);
            $wasPublished = $chapter->status === Chapter::STATUS_PUBLISHED;
            $publishChanged = $wasPublished !== $published;

            // Update basics
            $chapter->title = $title;
            $chapter->author_note = $authorNoteHtml;
            $chapter->content = $contentHtml;
            $chapter->status = $published ? Chapter::STATUS_PUBLISHED : Chapter::STATUS_NOT_PUBLISHED;

            // Handle first publish timestamp
            $firstPublishedNow = false;
            if ($published && !$wasPublished && $chapter->first_published_at === null) {
                $chapter->first_published_at = now();
                $firstPublishedNow = true;
            }

            // Regenerate slug base but keep id suffix stable
            $slugBase = Str::slug($title);
            $chapter->slug = SlugWithId::build($slugBase, $chapter->id);

            // Mark content as edited for display purposes
            $chapter->last_edited_at = now();
            $chapter->save();

            // Update story.last_chapter_published_at only if this request performed the first publish
            if ($firstPublishedNow || $publishChanged) {
                $this->updateStoryLastPublished($story);
            }

            // Snapshot AFTER changes
            $after = ChapterSnapshot::fromModel($chapter);

            // Emit Chapter.Published/Unpublished transitions on status change
            if ($publishChanged) {
                if ($published) {
                    $this->eventBus->emit(new ChapterPublished(
                        storyId: (int) $story->id,
                        chapter: $after,
                    ));
                } else {
                    $this->eventBus->emit(new ChapterUnpublished(
                        storyId: (int) $story->id,
                        chapter: $after,
                    ));
                }
            }

            // Emit update event with before/after
            $this->eventBus->emit(new ChapterUpdated(
                storyId: (int) $story->id,
                before: $before,
                after: $after,
            ));

            // Notify co-authors about the chapter update
            $this->notifyCoAuthorsOfChapterChange($story, $userId, 'updated', $title, $chapter->slug);

            return $chapter;
        });
    }

    // sanitizeAndValidate no longer needed; inputs are prepared in the FormRequest

    /**
     * Return chapters to display on story show page, filtered by viewer visibility,
     * with minimal selected fields and correct ordering.
     *
     * @return \Illuminate\Support\Collection<int, Chapter>
     */
    public function getChapters(Story $story, ?int $viewerId): \Illuminate\Support\Collection
    {
        $isAuthor = $story->isAuthor($viewerId);

        return $story->chapters()
            ->select(['id','title','slug','status','sort_order','reads_logged_count','word_count','character_count','last_edited_at'])
            ->when(!$isAuthor, fn($q) => $q->where('status', Chapter::STATUS_PUBLISHED))
            ->orderBy('sort_order','asc')
            ->get();
    }

    /**
     * Recompute and persist story.last_chapter_published_at if a newer first publish exists.
     */
    private function updateStoryLastPublished(Story $story): void
    {
        $latest = Chapter::where('story_id', $story->id)
            ->where('status', Chapter::STATUS_PUBLISHED)
            ->whereNotNull('first_published_at')
            ->max('first_published_at') ?? null;
        $story->last_chapter_published_at = $latest;
        $story->save();
    }

    /**
     * Hard delete a chapter. Do not recompute story.last_chapter_published_at (immutability per US-032/US-043).
     */
    public function deleteChapter(Story $story, Chapter $chapter, int $userId): void
    {
        DB::transaction(function () use ($story, $chapter, $userId) {
            // Safety: ensure chapter belongs to the story
            if ((int)$chapter->story_id !== (int)$story->id) {
                throw new \InvalidArgumentException('Chapter does not belong to given story');
            }

            // Capture snapshot before deletion for event payload
            $snapshot = ChapterSnapshot::fromModel($chapter);
            $chapterTitle = (string) $chapter->title;

            // Purge comments for this chapter (hard delete via maintenance API)
            $this->comments->deleteFor('chapter', (int) $chapter->id);

            // Rely on FK cascade to delete related reading_progress rows
            $chapter->forceDelete();

            $this->updateStoryLastPublished($story);

            // Emit Chapter.Deleted event
            $this->eventBus->emit(new \App\Domains\Story\Public\Events\ChapterDeleted(
                storyId: (int) $story->id,
                chapter: $snapshot,
            ));

            // Notify co-authors about the chapter deletion
            $this->notifyCoAuthorsOfChapterChange($story, $userId, 'deleted', $chapterTitle);
        });
    }

    /**
     * Return true when the given user is an author/co-author of the chapter's parent story.
     * Returns false if the chapter cannot be found.
     */
    public function isUserAuthorOfChapter(int $chapterId, int $userId): bool
    {
        // Single query: find any story that has the chapter and where the user is an author
        return Story::query()
            ->whereHas('chapters', function ($q) use ($chapterId) {
                $q->whereKey($chapterId);
            })
            ->whereHas('authors', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->exists();
    }

    /**
     * Notify all co-authors (except the acting user) about a chapter change.
     */
    private function notifyCoAuthorsOfChapterChange(
        Story $story,
        int $actingUserId,
        string $action,
        string $chapterTitle,
        ?string $chapterSlug = null
    ): void {
        // Get all author user IDs except the acting user
        $authorIds = $story->authors()->pluck('user_id')->map(fn($id) => (int)$id)->all();
        $recipientIds = array_values(array_filter($authorIds, fn($id) => $id !== $actingUserId));

        if (empty($recipientIds)) {
            return;
        }

        // Get acting user's profile
        $actingProfile = $this->profiles->getPublicProfile($actingUserId);
        if (!$actingProfile) {
            return;
        }

        $notification = match ($action) {
            'created' => new CoAuthorChapterCreatedNotification(
                userName: $actingProfile->display_name,
                userSlug: $actingProfile->slug,
                storyTitle: (string) $story->title,
                storySlug: (string) $story->slug,
                chapterTitle: $chapterTitle,
                chapterSlug: $chapterSlug ?? '',
            ),
            'updated' => new CoAuthorChapterUpdatedNotification(
                userName: $actingProfile->display_name,
                userSlug: $actingProfile->slug,
                storyTitle: (string) $story->title,
                storySlug: (string) $story->slug,
                chapterTitle: $chapterTitle,
                chapterSlug: $chapterSlug ?? '',
            ),
            'deleted' => new CoAuthorChapterDeletedNotification(
                userName: $actingProfile->display_name,
                userSlug: $actingProfile->slug,
                storyTitle: (string) $story->title,
                storySlug: (string) $story->slug,
                chapterTitle: $chapterTitle,
            ),
            default => null,
        };

        if ($notification) {
            $this->notifications->createNotification($recipientIds, $notification, $actingUserId);
        }
    }

    /**
     * Get published chapters by IDs with their stories (public/community only).
     * Returns only chapters whose story matches the visibility filter.
     *
     * @param array<int> $chapterIds
     * @return \Illuminate\Support\Collection<int, Chapter>
     */
    public function getPublishedChaptersWithPublicStories(array $chapterIds): \Illuminate\Support\Collection
    {
        if (empty($chapterIds)) {
            return collect();
        }

        return Chapter::query()
            ->whereIn('id', $chapterIds)
            ->where('status', Chapter::STATUS_PUBLISHED)
            ->with(['story' => function ($query) {
                $query->whereIn('visibility', [Story::VIS_PUBLIC, Story::VIS_COMMUNITY]);
            }])
            ->get()
            ->filter(fn(Chapter $c) => $c->story !== null);
    }
}
