<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Models\StoryRefAudience;

class AudienceService extends BaseRefService
{
    protected string $modelClass = StoryRefAudience::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = false;
}
