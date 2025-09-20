<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefTriggerWarning;

class TriggerWarningService extends BaseRefService
{
    protected string $modelClass = StoryRefTriggerWarning::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
    protected string $refKind = 'trigger_warning';
}
