<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StoryService
{
    /**
     * Generic listing with optional filters.
     *
     * @param int $page
     * @param int $perPage
     * @param array $visibilities Array of allowed visibilities (e.g., [Story::VIS_PUBLIC, Story::VIS_COMMUNITY, Story::VIS_PRIVATE])
     * @param int|null $userId If provided, only stories authored by this user
     * @param int|null $viewerId If provided, include private stories where this user is a collaborator
     */
    public function listStories(int $page, int $perPage = 24, array $visibilities = [Story::VIS_PUBLIC], ?int $userId = null, ?int $viewerId = null): LengthAwarePaginator
    {
        $query = Story::query()
            ->with(['authors', 'collaborators'])
            ->orderByDesc('created_at');

        if ($userId !== null) {
            $query->whereHas('authors', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        // Normalize visibilities; default to public if empty
        $visibilities = array_values($visibilities);
        if (empty($visibilities)) {
            $visibilities = [Story::VIS_PUBLIC];
        }

        $pubCom = array_values(array_intersect($visibilities, [Story::VIS_PUBLIC, Story::VIS_COMMUNITY]));
        $includePrivate = in_array(Story::VIS_PRIVATE, $visibilities, true);

        $query->where(function ($w) use ($pubCom, $includePrivate, $viewerId) {
            $addedAny = false;

            if (!empty($pubCom)) {
                $w->whereIn('visibility', $pubCom);
                $addedAny = true;
            }

            if ($includePrivate && $viewerId !== null) {
                if ($addedAny) {
                    $w->orWhere(function ($q) use ($viewerId) {
                        $q->where('visibility', Story::VIS_PRIVATE)
                          ->whereHas('collaborators', function ($c) use ($viewerId) {
                              $c->where('user_id', $viewerId);
                          });
                    });
                } else {
                    $w->where('visibility', Story::VIS_PRIVATE)
                      ->whereHas('collaborators', function ($c) use ($viewerId) {
                          $c->where('user_id', $viewerId);
                      });
                }
            }
        });

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
