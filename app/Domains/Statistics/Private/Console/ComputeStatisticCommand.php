<?php

namespace App\Domains\Statistics\Private\Console;

use App\Domains\Statistics\Private\Services\StatisticRegistry;
use Illuminate\Console\Command;

class ComputeStatisticCommand extends Command
{
    protected $signature = 'statistics:compute 
                            {key : The statistic key to compute (e.g., global.total_users)}
                            {--scope-id= : The scope ID (optional for global scope)}';

    protected $description = 'Recompute a statistic from scratch. Each statistic handles its own recompute logic.';

    public function __construct(
        private readonly StatisticRegistry $registry,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $key = $this->argument('key');
        $scopeId = $this->option('scope-id');

        $definition = $this->registry->resolve($key);

        if ($definition === null) {
            $this->error("Unknown statistic: {$key}");
            $this->info('Available statistics:');
            foreach ($this->registry->all() as $statClass) {
                $this->line("  - {$statClass::key()}");
            }
            return Command::FAILURE;
        }

        $this->info("Recomputing statistic: {$key}");

        $result = $definition->recompute($scopeId);

        $this->info("Snapshot value: " . ($result->snapshotValue ?? 'null'));
        if ($result->eventsProcessed > 0) {
            $this->info("Events processed: {$result->eventsProcessed}");
        }
        if ($result->timeSeriesPoints > 0) {
            $this->info("Time-series data points: {$result->timeSeriesPoints}");
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
