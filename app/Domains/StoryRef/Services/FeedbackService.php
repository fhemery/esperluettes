<?php

namespace App\Domains\StoryRef\Services;

use App\Domains\StoryRef\Models\StoryRefFeedback;

class FeedbackService extends BaseRefService
{
    protected string $modelClass = StoryRefFeedback::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = false;
    protected string $refKind = 'feedback';
}
