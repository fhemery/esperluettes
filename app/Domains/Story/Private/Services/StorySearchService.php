<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Story\Models\Story;
use App\Domains\Auth\PublicApi\AuthPublicApi;
use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Support\Facades\Auth;

class StorySearchService
{

    public function __construct(
        private AuthPublicApi $authPublicApi,
    ) {}
    /**
     * Perform visibility-aware search and return an array with:
     *  - rows: Illuminate\Support\Collection<Story> (max $limit)
     *  - total: int (uncapped total)
     */
    public function search(string $query, int $limit = 25): array
    {
       
        $q = trim($query);
        if ($q === '') {
            return ['rows' => collect(), 'total' => 0];
        }
        $cap = max(1, min(25, (int) $limit));

        $qLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';

        $base = Story::query()->with(['authors:user_id']);

        // Visibility enforcement
        $base->where(function ($w) {
            $userId = Auth::id();
            $isConfirmed = $userId ? $this->authPublicApi->hasAnyRole([Roles::USER_CONFIRMED]) : false;

            if ($isConfirmed) {
                // confirmed: public + community OR private as collaborator
                $w->whereIn('visibility', [Story::VIS_PUBLIC, Story::VIS_COMMUNITY])
                  ->orWhere(function ($q) use ($userId) {
                      $q->where('visibility', Story::VIS_PRIVATE)
                        ->whereHas('collaborators', function ($c) use ($userId) {
                            $c->where('user_id', $userId);
                        });
                  });
            } else {
                // guest or non-confirmed: public only
                $w->where('visibility', Story::VIS_PUBLIC);
            }
        });

        // Matching: title, and description only when len > 4
        $base->where(function ($w) use ($qLike, $q) {
            $w->where('title', 'like', $qLike);
            if (mb_strlen($q) > 4) {
                $w->orWhere('description', 'like', $qLike);
            }
        });

        $total = (int) $base->count('id');

        $rows = $base->orderByDesc('last_chapter_published_at')
            ->orderByDesc('created_at')
            ->limit($cap)
            ->get(['id', 'slug', 'title']);

        return [
            'rows' => $rows,
            'total' => $total,
        ];
    }
}
