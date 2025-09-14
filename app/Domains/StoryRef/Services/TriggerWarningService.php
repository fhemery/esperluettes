<?php

namespace App\Domains\StoryRef\Services;

use App\Domains\StoryRef\Models\StoryRefTriggerWarning;

class TriggerWarningService extends BaseRefService
{
    protected string $modelClass = StoryRefTriggerWarning::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
    protected string $refKind = 'trigger_warning';
}
