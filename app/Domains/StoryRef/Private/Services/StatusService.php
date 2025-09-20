<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefStatus;

class StatusService extends BaseRefService
{
    protected string $modelClass = StoryRefStatus::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
    protected string $refKind = 'status';
}
