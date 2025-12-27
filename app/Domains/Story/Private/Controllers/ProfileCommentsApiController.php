<?php

namespace App\Domains\Story\Private\Controllers;

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Services\ChapterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProfileCommentsApiController extends Controller
{
    public function __construct(
        private CommentPublicApi $commentApi,
        private ChapterService $chapterService,
    ) {}

    /**
     * Get comments for a specific story by a specific user.
     * Returns chapter titles with their comment bodies.
     */
    public function getCommentsForStory(int $storyId, int $userId): JsonResponse
    {
        // Get all chapter IDs where the user has root comments
        $allChapterIds = $this->commentApi->getEntityIdsWithRootCommentsByAuthor('chapter', $userId);

        if (empty($allChapterIds)) {
            return response()->json(['comments' => []]);
        }

        // Load published chapters for this story only
        $chapters = $this->chapterService->getPublishedChaptersWithPublicStories($allChapterIds)
            ->filter(fn(Chapter $c) => (int) $c->story_id === $storyId)
            ->sortBy('sort_order');

        if ($chapters->isEmpty()) {
            return response()->json(['comments' => []]);
        }

        // Get the actual comments
        $chapterIds = $chapters->pluck('id')->map(fn($id) => (int) $id)->all();
        $comments = $this->commentApi->getRootCommentsByAuthorAndEntities('chapter', $userId, $chapterIds);

        // Build response
        $result = [];
        foreach ($chapters as $chapter) {
            $chapterId = (int) $chapter->id;
            if (isset($comments[$chapterId])) {
                $result[] = [
                    'chapterTitle' => $chapter->title,
                    'chapterSlug' => $chapter->slug,
                    'storySlug' => $chapter->story->slug,
                    'body' => $comments[$chapterId]->body,
                ];
            }
        }

        return response()->json(['comments' => $result]);
    }
}
