<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Models\StoryRefFeedback;

class FeedbackService extends BaseRefService
{
    protected string $modelClass = StoryRefFeedback::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = false;
}
