<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Fidry\CpuCoreCounter\CpuCoreCounter;
use Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;
use Illuminate\Console\Command;

class ParallelTestCommand extends Command
{
    protected $signature = 'test:parallel';
    protected $description = 'Run tests in parallel, auto-scaling to 80% of available CPU cores (override with TEST_PROCESSES env var).';

    public function handle(): int
    {
        $processes = $this->resolveProcessCount();

        // TestCommand reads raw $_SERVER['argv'] to build its paratest arguments,
        // so we inject --parallel and --processes there before delegating.
        $extraArgs = array_slice($_SERVER['argv'], 2);
        $_SERVER['argv'] = [
            $_SERVER['argv'][0],
            'test',
            '--parallel',
            "--processes={$processes}",
            ...$extraArgs,
        ];

        return $this->call('test', ['--parallel' => true]);
    }

    private function resolveProcessCount(): int
    {
        $env = (int) getenv('TEST_PROCESSES');
        if ($env > 0) {
            return $env;
        }

        try {
            $cores = (new CpuCoreCounter())->getCount();
            return max(1, (int) ($cores * 0.8));
        } catch (NumberOfCpuCoreNotFound) {
            return 1;
        }
    }
}
