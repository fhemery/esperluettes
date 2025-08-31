<?php

namespace App\Domains\Story\Services;

use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Shared\Support\SparseReorder;
use App\Domains\Story\Http\Requests\ChapterRequest;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\Story;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChapterService
{
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
                $this->updateStoryLastPublishedIfLatest($story);
            }

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
            $wasPublished = $chapter->status === Chapter::STATUS_PUBLISHED;

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
            if ($firstPublishedNow) {
                $this->updateStoryLastPublishedIfLatest($story);
            }

            return $chapter;
        });
    }

    // sanitizeAndValidate no longer needed; inputs are prepared in the FormRequest

    /**
     * Recompute and persist story.last_chapter_published_at if a newer first publish exists.
     */
    private function updateStoryLastPublishedIfLatest(Story $story): void
    {
        $latest = Chapter::where('story_id', $story->id)
            ->whereNotNull('first_published_at')
            ->max('first_published_at');
        if ($latest && ($story->last_chapter_published_at === null || $latest > $story->last_chapter_published_at)) {
            $story->last_chapter_published_at = $latest;
            $story->save();
        }
    }
}
