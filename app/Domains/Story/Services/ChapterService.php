<?php

namespace App\Domains\Story\Services;

use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Shared\Support\SparseReorder;
use App\Domains\Story\Http\Requests\ChapterRequest;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\Story;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Story\Events\ChapterCreated;
use App\Domains\Story\Events\ChapterUpdated;
use App\Domains\Story\Events\ChapterPublished;
use App\Domains\Story\Events\ChapterUnpublished;
use App\Domains\Story\Events\DTO\ChapterSnapshot;
use App\Domains\Comment\PublicApi\CommentMaintenancePublicApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChapterService
{
    public function __construct(
        private readonly CommentMaintenancePublicApi $comments,
        private readonly EventBus $eventBus,
    ) {}

    public function createChapter(Story $story, ChapterRequest $request, int $userId): Chapter
    {
        $title = (string) $request->input('title');
        $authorNoteHtml = $request->input('author_note'); 
        $contentHtml = (string) $request->input('content');
        $published = (bool)($request->boolean('published', false));

        return DB::transaction(function () use ($story, $title, $authorNoteHtml, $contentHtml, $published) {
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

            return $chapter;
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

    public function updateChapter(Story $story, Chapter $chapter, ChapterRequest $request): Chapter
    {
        $title = (string) $request->input('title');
        $authorNoteHtml = $request->input('author_note'); // purified or null
        $contentHtml = (string) $request->input('content'); // purified
        $published = (bool)($request->boolean('published', false));

        return DB::transaction(function () use ($story, $chapter, $title, $authorNoteHtml, $contentHtml, $published) {
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
            ->select(['id','title','slug','status','sort_order','reads_logged_count','word_count','character_count'])
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
    public function deleteChapter(Story $story, Chapter $chapter): void
    {
        DB::transaction(function () use ($story, $chapter) {
            // Safety: ensure chapter belongs to the story
            if ((int)$chapter->story_id !== (int)$story->id) {
                throw new \InvalidArgumentException('Chapter does not belong to given story');
            }

            // Capture snapshot before deletion for event payload
            $snapshot = ChapterSnapshot::fromModel($chapter);

            // Purge comments for this chapter (hard delete via maintenance API)
            $this->comments->deleteFor('chapter', (int) $chapter->id);

            // Rely on FK cascade to delete related reading_progress rows
            $chapter->delete();

            $this->updateStoryLastPublished($story);

            // Emit Chapter.Deleted event
            $this->eventBus->emit(new \App\Domains\Story\Events\ChapterDeleted(
                storyId: (int) $story->id,
                chapter: $snapshot,
            ));
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
}
