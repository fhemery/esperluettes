<?php

namespace App\Domains\Story\Private\ViewModels;

use App\Domains\Shared\Dto\ProfileDto;

class ProfileCommentsAuthorViewModel
{
    /** @var ProfileDto[] */
    public readonly array $authors;

    /** @var ProfileCommentsStoryViewModel[] */
    public readonly array $stories;

    /**
     * @param ProfileDto[] $authors Authors sorted by display_name
     * @param ProfileCommentsStoryViewModel[] $stories Stories sorted by title
     */
    public function __construct(
        array $authors,
        public readonly int $totalCommentCount,
        array $stories,
    ) {
        $this->authors = $authors;
        $this->stories = $stories;
    }

    /**
     * Get a unique key for this author group (sorted user IDs joined by dash)
     */
    public function getAuthorGroupKey(): string
    {
        $ids = array_map(fn(ProfileDto $p) => $p->user_id, $this->authors);
        sort($ids);
        return implode('-', $ids);
    }
}
