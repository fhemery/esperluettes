<?php

namespace App\Domains\StoryRef\Services;

use App\Domains\StoryRef\Models\StoryRefGenre;

class GenreService extends BaseRefService
{
    protected string $modelClass = StoryRefGenre::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
    protected string $refKind = 'genre';
}
