<?php

namespace App\Domains\StoryRef\Private\Services;

use App\Domains\StoryRef\Private\Models\StoryRefCopyright;

class CopyrightService extends BaseRefService
{
    protected string $modelClass = StoryRefCopyright::class;
    protected bool $hasOrder = true;
    protected bool $hasDescription = true;
    protected string $refKind = 'copyright';
}
