<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefType;

class TypeService extends BaseRefService
{
    protected string $modelClass = StoryRefType::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = false;
    protected string $refKind = 'type';
}
