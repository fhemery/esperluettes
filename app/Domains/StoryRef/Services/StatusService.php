<?php

namespace App\Domains\StoryRef\Services;

use App\Domains\StoryRef\Models\StoryRefStatus;

class StatusService extends BaseRefService
{
    protected string $modelClass = StoryRefStatus::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
}
