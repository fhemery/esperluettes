<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefAudience;

class AudienceService extends BaseRefService
{
    protected string $modelClass = StoryRefAudience::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = false;
    protected string $refKind = 'audience';
}
