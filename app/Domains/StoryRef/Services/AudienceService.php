<?php

namespace App\Domains\StoryRef\Services;

use App\Domains\StoryRef\Models\StoryRefAudience;

class AudienceService extends BaseRefService
{
    protected string $modelClass = StoryRefAudience::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = false;
}
