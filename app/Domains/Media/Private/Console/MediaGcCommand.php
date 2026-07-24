<?php

declare(strict_types=1);

namespace App\Domains\Media\Private\Console;

use App\Domains\Media\Private\Services\MediaService;
use Illuminate\Console\Command;

class MediaGcCommand extends Command
{
    protected $signature = 'media:gc {--days=7 : Grace window in days before an unclaimed file is deleted} {--dry-run : Report what would be deleted without deleting}';

    protected $description = 'Delete stored images no content uses anymore, past the grace window. Skips whole scopes with no registered usage provider.';

    public function handle(MediaService $media): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        $result = $media->gc($days, $dryRun);

        foreach ($result['skipped'] as $folder) {
            $this->warn("Skipped unclaimed scope (no provider reports paths under it): {$folder}");
        }

        $verb = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$verb} " . count($result['deleted']) . ' unused image(s).');
        foreach ($result['deleted'] as $path) {
            $this->line("  - {$path}");
        }

        return self::SUCCESS;
    }
}
