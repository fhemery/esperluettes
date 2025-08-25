<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use App\Domains\Story\Support\StoryFilterAndPagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StoryService
{
    /**
     * Generic listing with optional filters.
     *
     * @param StoryFilterAndPagination $filter Filters and pagination (page, perPage, visibilities, userId)
     * @param int|null $viewerId If provided, include private stories where this user is a collaborator
     */
    public function listStories(StoryFilterAndPagination $filter, ?int $viewerId = null): LengthAwarePaginator
    {
        $query = Story::query()
            ->with(['authors', 'collaborators', 'genres:id'])
            ->orderByDesc('created_at');

        if ($filter->userId !== null) {
            $query->whereHas('authors', function ($q) use ($filter) {
                $q->where('user_id', $filter->userId);
            });
        }

        // Filter by Type if provided
        if ($filter->typeId !== null) {
            $query->where('story_ref_type_id', $filter->typeId);
        }

        // Filter by Audience if provided (multi-select)
        if (!empty($filter->audienceIds)) {
            $query->whereIn('story_ref_audience_id', $filter->audienceIds);
        }

        // Filter by Genres (AND semantics: story must have all selected genre IDs)
        if (!empty($filter->genreIds)) {
            foreach ($filter->genreIds as $gid) {
                $query->whereHas('genres', function ($q) use ($gid) {
                    $q->where('story_ref_genres.id', $gid);
                });
            }
        }

        // Visibilities already normalized in DTO
        $visibilities = $filter->visibilities;

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
        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
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
            // Set mandatory reference fields
            $story->story_ref_type_id = (int) $request->input('story_ref_type_id');
            $story->story_ref_audience_id = (int) $request->input('story_ref_audience_id');
            $story->story_ref_copyright_id = (int) $request->input('story_ref_copyright_id');
            $statusId = $request->input('story_ref_status_id');
            if ($statusId !== null) {
                $story->story_ref_status_id = (int) $statusId;
            }
            $story->save();

            // 2) Update slug with id suffix
            $story->slug = $slugBase . '-' . $story->id;
            $story->save();

            // 3) Attach genres (1..3)
            $genreIds = $request->input('story_ref_genre_ids', []);
            if (is_array($genreIds)) {
                $ids = array_values(array_unique(array_map('intval', $genreIds)));
                $story->genres()->sync($ids);
            }

            // 3b) Attach trigger warnings (optional)
            $twIds = $request->input('story_ref_trigger_warning_ids', []);
            if (is_array($twIds)) {
                $ids = array_values(array_unique(array_map('intval', $twIds)));
                $story->triggerWarnings()->sync($ids);
            }

            // 4) Seed collaborator row for creator
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

        // Only eager-load genre IDs to minimize payload; names are resolved via lookup in controller
        $base = Story::query()->with([
            'authors',
            'genres:id',
            'triggerWarnings:id',
        ]);
        $story = $id
            ? $base->findOrFail($id)
            : $base->where('slug', $slug)->firstOrFail();

        return $story;
    }
}
