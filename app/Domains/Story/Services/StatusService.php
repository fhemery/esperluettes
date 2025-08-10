<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Models\StoryRefStatus;

class StatusService extends BaseRefService
{
    protected string $modelClass = StoryRefStatus::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
}
