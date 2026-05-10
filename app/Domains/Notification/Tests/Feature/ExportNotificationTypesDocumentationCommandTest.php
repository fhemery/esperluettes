<?php

use App\Domains\News\Public\Notifications\NewsPublishedNotification;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class);

describe('notifications:export-types-doc', function () {
    it('writes a markdown file listing registered types and channels', function () {
        $path = sys_get_temp_dir().'/notification-types-test-'.uniqid('', true).'.md';

        $exit = Artisan::call('notifications:export-types-doc', [
            '--output' => $path,
        ]);

        expect($exit)->toBe(0);
        expect(file_exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('notifications:export-types-doc');
        expect($content)->toContain('news.published');
        expect($content)->toContain(NewsPublishedNotification::class);
        expect($content)->toContain('`discord`');
        expect($content)->toContain('Delivery channels');

        unlink($path);
    });
});
