<?php

namespace App\Domains\Story\Services;

use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Story\Http\Requests\ChapterRequest;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\Story;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class ChapterService
{
    public function createChapter(Story $story, ChapterRequest $request, int $userId): Chapter
    {
        // Default to false if checkbox not provided
        $published = (bool)($request->boolean('published', false));
        // Shared sanitize + validate
        [$title, $authorNoteHtml, $contentHtml] = $this->sanitizeAndValidate($request);

        return DB::transaction(function () use ($story, $title, $authorNoteHtml, $contentHtml, $published) {
            // compute sparse sort order
            $maxOrder = (int) (Chapter::where('story_id', $story->id)->max('sort_order') ?? 0);
            $sortOrder = $maxOrder + 100;

            // temporary slug base (without id)
            $slugBase = Str::slug($title);

            $chapter = new Chapter();
            $chapter->story_id = $story->id;
            $chapter->title = $title;
            $chapter->slug = $slugBase; // will update with id suffix after save
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

    public function updateChapter(Story $story, Chapter $chapter, ChapterRequest $request): Chapter
    {
        $published = (bool)($request->boolean('published', false));
        // Shared sanitize + validate
        [$title, $authorNoteHtml, $contentHtml] = $this->sanitizeAndValidate($request);

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

    /**
     * Sanitize inputs and validate logical constraints shared by create/update.
     * @return array{0:string,1:?string,2:string} [$title, $authorNoteHtml, $contentHtml]
     * @throws ValidationException
     */
    private function sanitizeAndValidate(ChapterRequest $request): array
    {
        $title = trim($request->input('title'));
        $authorNoteRaw = $request->input('author_note');
        $contentRaw = $request->input('content');

        $authorNoteHtml = $authorNoteRaw ? Purifier::clean($authorNoteRaw, 'strict') : null;
        $contentHtml = Purifier::clean($contentRaw ?? '', 'strict');

        // logical length for author_note (strip tags after purification)
        if ($authorNoteHtml !== null) {
            $plain = trim(strip_tags($authorNoteHtml));
            if (mb_strlen($plain) > 1000) {
                throw ValidationException::withMessages([
                    'author_note' => __('story::validation.author_note_too_long'),
                ]);
            }
        }

        // Ensure non-empty title after trim
        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => __('validation.required', ['attribute' => 'title']),
            ]);
        }

        // Ensure non-empty content after purification (strip tags and trim)
        $contentPlain = trim(strip_tags($contentHtml));
        if ($contentPlain === '') {
            throw ValidationException::withMessages([
                'content' => __('validation.required', ['attribute' => 'content']),
            ]);
        }

        return [$title, $authorNoteHtml, $contentHtml];
    }

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
