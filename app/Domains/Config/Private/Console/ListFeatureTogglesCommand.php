<?php

namespace App\Domains\Config\Private\Console;

use App\Domains\Config\Public\Services\FeatureToggleService;
use Illuminate\Console\Command;

class ListFeatureTogglesCommand extends Command
{
    protected $signature = 'config:toggles {--json : Print the report as JSON}';

    protected $description = 'List declared feature toggles and orphan rows (read-only)';

    public function handle(FeatureToggleService $service): int
    {
        $report = $service->report();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->table(
            ['domain', 'name', 'declared', 'access', 'roles', 'updated_at'],
            array_map(fn (array $entry) => [
                $entry['domain'],
                $entry['name'],
                $entry['declared'] ? 'yes' : 'no',
                $entry['access'],
                implode(', ', $entry['roles']),
                $entry['updated_at'] ?? '',
            ], $report),
        );

        return self::SUCCESS;
    }
}
