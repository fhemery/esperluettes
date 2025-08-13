<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Models\StoryRefTriggerWarning;

class TriggerWarningService extends BaseRefService
{
    protected string $modelClass = StoryRefTriggerWarning::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
}
