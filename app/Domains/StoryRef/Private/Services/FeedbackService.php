<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefFeedback;

class FeedbackService extends BaseRefService
{
    protected string $modelClass = StoryRefFeedback::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = false;
    protected string $refKind = 'feedback';
}
