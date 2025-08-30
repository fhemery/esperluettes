<?php

namespace App\Domains\Story\Http\Controllers;

use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\Story;
use App\Domains\Story\Services\ReadingProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReadingProgressController
{
    public function __construct(
        private ReadingProgressService $readingProgress
    ) {
    }

    public function markRead(Request $request, string $storySlug, string $chapterSlug)
    {
        $user = $request->user();

        $storyId = SlugWithId::extractId($storySlug);
        $chapterId = SlugWithId::extractId($chapterSlug);

        $story = Story::query()->findOrFail($storyId);
        $chapter = Chapter::query()->where('story_id', $story->id)->findOrFail($chapterId);

        $this->assertChapterViewableAndPublished($story, $chapter);
        // Authors/co-authors forbidden
        if ($story->isAuthor((int)$user->id)) {
            abort(403);
        }

        $this->readingProgress->markRead((int) $user->id, $story, $chapter);

        return response()->noContent(); // 204
    }

    public function unmarkRead(Request $request, string $storySlug, string $chapterSlug)
    {
        $user = $request->user();

        $storyId = SlugWithId::extractId($storySlug);
        $chapterId = SlugWithId::extractId($chapterSlug);

        $story = Story::query()->findOrFail($storyId);
        $chapter = Chapter::query()->where('story_id', $story->id)->findOrFail($chapterId);

        $this->assertChapterViewableAndPublished($story, $chapter);
        if ($story->isAuthor((int)$user->id)) {
            abort(403);
        }

        $this->readingProgress->unmarkRead((int) $user->id, $story, $chapter);

        return response()->noContent(); // 204
    }

    public function guestIncrement(Request $request, string $storySlug, string $chapterSlug)
    {
        // Guests only
        $storyId = SlugWithId::extractId($storySlug);
        $chapterId = SlugWithId::extractId($chapterSlug);

        $story = Story::query()->findOrFail($storyId);
        $chapter = Chapter::query()->where('story_id', $story->id)->findOrFail($chapterId);

        $this->assertChapterViewableAndPublished($story, $chapter);

        $user = $request->user();
        if ($user) {
            abort(403);
        }

        // Delegate to service
        $this->readingProgress->incrementGuest($chapter);

        return response()->noContent(); // 204
    }

    private function assertChapterViewableAndPublished(Story $story, Chapter $chapter): void
    {
        if (!Gate::allows('view', $story)) {
            abort(404);
        }
        if ($chapter->status !== Chapter::STATUS_PUBLISHED) {
            abort(404);
        }
    }
}
