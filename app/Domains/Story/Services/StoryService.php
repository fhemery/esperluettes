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
        /** @var LengthAwarePaginator $stories */
        return Story::query()
            ->with(['authors'])
            ->where('visibility', Story::VIS_PUBLIC)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Generic listing with optional filters.
     *
     * @param int $page
     * @param int $perPage
     * @param array $visibilities Array of allowed visibilities (e.g., [Story::VIS_PUBLIC])
     * @param int|null $userId If provided, only stories authored by this user
     */
    public function listStories(int $page, int $perPage = 24, array $visibilities = [Story::VIS_PUBLIC], ?int $userId = null): LengthAwarePaginator
    {
        $query = Story::query()
            ->with(['authors'])
            ->orderByDesc('created_at');

        if (!empty($visibilities)) {
            $query->whereIn('visibility', $visibilities);
        }

        if ($userId !== null) {
            $query->whereHas('authors', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        /** @var LengthAwarePaginator $stories */
        return $query->paginate($perPage, ['*'], 'page', $page);
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

    public function getStoryForShow(string $slug, ?int $viewerId): Story
    {
        // Extract id from trailing -{id}
        $id = null;
        if (preg_match('/-(\d+)$/', $slug, $m)) {
            $id = (int) $m[1];
        }

        $base = Story::query();
        $story = $id
            ? $base->findOrFail($id)
            : $base->where('slug', $slug)->firstOrFail();

        return $story;
    }
}
