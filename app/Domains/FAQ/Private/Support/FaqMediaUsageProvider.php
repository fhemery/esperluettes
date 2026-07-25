<?php

declare(strict_types=1);

namespace App\Domains\FAQ\Private\Support;

use App\Domains\FAQ\Private\Models\FaqQuestion;
use App\Domains\Media\Public\Contracts\MediaUsageProvider;

/**
 * Reports every FAQ question image path so the Media GC never collects a file
 * still referenced by a question.
 */
final class FaqMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        return FaqQuestion::query()
            ->whereNotNull('image_path')
            ->pluck('image_path')
            ->all();
    }
}
