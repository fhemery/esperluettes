<?php

namespace App\Domains\Story\Private\Models;

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Models\Chapter;

class StoryWithNextChapter
{
    public function __construct(
        public readonly Story $story,
        public readonly ?Chapter $nextChapter = null,
    ) {
    }
}
