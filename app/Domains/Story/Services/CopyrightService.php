<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Models\StoryRefCopyright;

class CopyrightService extends BaseRefService
{
    protected string $modelClass = StoryRefCopyright::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
}
