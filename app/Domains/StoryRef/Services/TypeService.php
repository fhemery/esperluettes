<?php

namespace App\Domains\StoryRef\Services;

use App\Domains\StoryRef\Models\StoryRefType;

class TypeService extends BaseRefService
{
    protected string $modelClass = StoryRefType::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = false;
    protected string $refKind = 'type';
}
