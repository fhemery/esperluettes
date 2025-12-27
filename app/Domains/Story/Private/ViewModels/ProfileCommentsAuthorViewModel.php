<?php

namespace App\Domains\Story\Private\ViewModels;

use App\Domains\Shared\Dto\ProfileDto;

class ProfileCommentsAuthorViewModel
{
    /** @var ProfileCommentsStoryViewModel[] */
    public readonly array $stories;

    /**
     * @param ProfileCommentsStoryViewModel[] $stories
     */
    public function __construct(
        public readonly ProfileDto $author,
        public readonly int $totalCommentCount,
        array $stories,
    ) {
        $this->stories = $stories;
    }
}
