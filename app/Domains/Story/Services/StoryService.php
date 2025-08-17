<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StoryService
{
    public function listPublicStories(int $page, int $perPage = 24, int $ttlSeconds = 60): LengthAwarePaginator
    {
        $cacheKey = "stories.index.page." . $page;

        /** @var LengthAwarePaginator $stories */
        $stories = Cache::remember($cacheKey, $ttlSeconds, function () use ($perPage) {
            return Story::query()
                ->with(['authors:id,name'])
                ->where('visibility', Story::VIS_PUBLIC)
                ->orderByDesc('created_at')
                ->paginate($perPage);
        });

        return $stories;
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
            $story->save();

            // 2) Update slug with id suffix
            $story->slug = $slugBase . '-' . $story->id;
            $story->save();

            // 3) Seed collaborator row for creator
            DB::table('story_collaborators')->insert([
                'story_id' => $story->id,
                'user_id' => $userId,
                'role' => 'author',
                'invited_by_user_id' => $userId,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            return $story;
        });
    }

    /**
     * Returns [Story $story, bool $isAuthor]
     */
    public function getStoryForShow(string $slug, ?int $viewerId): array
    {
        // Extract id from trailing -{id}
        $id = null;
        if (preg_match('/-(\d+)$/', $slug, $m)) {
            $id = (int) $m[1];
        }

        $story = $id
            ? Story::query()->findOrFail($id)
            : Story::query()->where('slug', $slug)->firstOrFail();

        $isAuthor = false;
        if (!is_null($viewerId)) {
            $isAuthor = DB::table('story_collaborators')
                ->where('story_id', $story->id)
                ->where('user_id', $viewerId)
                ->where('role', 'author')
                ->exists();
        }

        return [$story, $isAuthor];
    }
}
