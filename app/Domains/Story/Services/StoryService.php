<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StoryService
{
    public function __construct(private readonly ProfilePublicApi $profiles)
    {
    }
    public function listPublicStories(int $page, int $perPage = 24, int $ttlSeconds = 60): LengthAwarePaginator
    {
        $cacheKey = "stories.index.page." . $page;

        /** @var LengthAwarePaginator $stories */
        $stories = Cache::remember($cacheKey, $ttlSeconds, function () use ($perPage) {
            return Story::query()
                ->with(['authors'])
                ->where('visibility', Story::VIS_PUBLIC)
                ->orderByDesc('created_at')
                ->paginate($perPage);
        });

        // Decorate authors for each story with public profile data (transient attributes)
        $allAuthorIds = $stories->getCollection()->flatMap(fn ($s) => $s->authors->pluck('user_id'))->unique()->values()->all();
        if (!empty($allAuthorIds)) {
            $profiles = $this->profiles->getPublicProfiles($allAuthorIds); // [userId => ProfileDto]
            foreach ($stories->getCollection() as $story) {
                foreach ($story->authors as $author) {
                    $dto = $profiles[$author->user_id] ?? null;
                    if ($dto) {
                        $author->name = $dto->display_name;
                        $author->avatar_url = $dto->avatar_url;
                        $author->profile_slug = $dto->slug;
                    } else {
                        $author->name = 'user-' . $author->user_id;
                    }
                }
            }
        }

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
