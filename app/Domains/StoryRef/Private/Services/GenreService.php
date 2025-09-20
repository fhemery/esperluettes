<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefGenre;

class GenreService extends BaseRefService
{
    protected string $modelClass = StoryRefGenre::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
    protected string $refKind = 'genre';
}
