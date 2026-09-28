<?php

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

uses(TestCase::class);

it('forbids x-shared::flash-block in admin page views, the admin layout owns it', function () {
    $finder = (new Finder())
        ->files()
        ->in(base_path('app/Domains/*/Private/Resources/views/pages/admin'))
        ->name('*.blade.php');

    $scanned = 0;
    $offenders = [];
    foreach ($finder as $file) {
        $scanned++;
        if (str_contains($file->getContents(), 'x-shared::flash-block')) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($scanned)->toBeGreaterThan(0);
    expect($offenders)->toBe(
        [],
        "The admin layout (<x-admin::layout>) already renders <x-shared::flash-block />; remove it from:\n".implode("\n", $offenders)
    );
});
