<?php

namespace App\Domains\Story\Private\Console;

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Services\ChapterService;
use Illuminate\Console\Command;

class PublishScheduledChaptersCommand extends Command
{
    protected $signature = 'story:publish-scheduled-chapters';
    protected $description = 'Publish chapters whose scheduled publication date has been reached';

    public function handle(ChapterService $chapterService): int
    {
        $chapters = Chapter::query()
            ->where('status', Chapter::STATUS_NOT_PUBLISHED)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->with('story')
            ->get();

        $count = 0;
        foreach ($chapters as $chapter) {
            $chapterService->publishScheduledChapter($chapter);
            $count++;
        }

        $this->info("Published: {$count} scheduled chapter(s).");

        return self::SUCCESS;
    }
}
