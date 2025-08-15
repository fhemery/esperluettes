<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Models\StoryRefGenre;

class GenreService extends BaseRefService
{
    protected string $modelClass = StoryRefGenre::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
}
