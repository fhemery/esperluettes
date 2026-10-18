<?php

namespace App\Domains\Fanart\Private\Support;

use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Contracts\StorySummaryDto;

/** Resolves the `{slug}` of every `/stories/{slug}/…` Fanart route; 404 when the story is missing. */
class StoryResolver
{
    public function __construct(private readonly StoryPublicApi $stories)
    {
    }

    public function resolve(string $slug): StorySummaryDto
    {
        $id = SlugWithId::extractId($slug);
        $story = $id !== null ? $this->stories->getStory($id) : null;

        if ($story === null) {
            abort(404);
        }

        return $story;
    }
}
