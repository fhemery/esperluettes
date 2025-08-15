<?php

namespace App\Domains\StoryRef\Services;

use App\Domains\StoryRef\Models\StoryRefCopyright;

class CopyrightService extends BaseRefService
{
    protected string $modelClass = StoryRefCopyright::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
}
