<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('accepts multiple directory arguments without Too many arguments', function () {
    putenv('TEST_PROCESSES=1');

    $this->withoutMockingConsoleOutput();

    $exitCode = $this->artisan('test:parallel', [
        'dirs' => [
            'tests/fixtures/parallel-paths/a',
            'tests/fixtures/parallel-paths/b',
        ],
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->not->toContain('Too many arguments')
        ->and($output)->not->toContain('expected arguments "path"');
});

it('still runs a single directory argument', function () {
    putenv('TEST_PROCESSES=1');

    $this->artisan('test:parallel', [
        'dirs' => [
            'tests/fixtures/parallel-paths/a',
        ],
    ])->assertExitCode(0);
});
