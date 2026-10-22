<?php

use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('private');
});

it('copies a private original into moderation/{topic} under a new name', function () {
    $api = app(MediaPublicApi::class);
    $source = $api->storePrivate('fanart/12', UploadedFile::fake()->image('art.png', 300, 200), [400, 800]);

    $copy = $api->copyPrivate($source, 'moderation/fanart');

    expect($copy)->toMatch('#^moderation/fanart/[A-Za-z0-9]{40}\.png$#');
    expect(basename($copy))->not->toBe(basename($source));
    expect(Storage::disk('private')->get($copy))->toBe(Storage::disk('private')->get($source));
    // Variants are not copied.
    expect(Storage::disk('private')->files('moderation/fanart'))->toBe([$copy]);
});

it('copies a public original into a private scope', function () {
    $api = app(MediaPublicApi::class);
    $source = $api->store('news', UploadedFile::fake()->image('photo.jpg', 300, 200));

    $copy = $api->copyPrivate($source, 'moderation/news');

    expect($copy)->toStartWith('moderation/news/')->toEndWith('.jpg');
    expect(Storage::disk('private')->get($copy))->toBe(Storage::disk('public')->get($source));
    Storage::disk('public')->assertMissing($copy);
});

it('refuses a public target scope', function () {
    $api = app(MediaPublicApi::class);
    $source = $api->storePrivate('fanart/12', UploadedFile::fake()->image('art.jpg', 300, 200));

    expect(fn () => $api->copyPrivate($source, 'news'))->toThrow(InvalidArgumentException::class);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('leaves the source file untouched', function () {
    $api = app(MediaPublicApi::class);
    $source = $api->storePrivate('fanart/12', UploadedFile::fake()->image('art.jpg', 300, 200));
    $before = Storage::disk('private')->get($source);

    $api->copyPrivate($source, 'moderation/fanart');

    Storage::disk('private')->assertExists($source);
    expect(Storage::disk('private')->get($source))->toBe($before);
});
