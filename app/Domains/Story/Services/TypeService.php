<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Models\StoryRefType;

class TypeService extends BaseRefService
{
    protected string $modelClass = StoryRefType::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = false;
}
