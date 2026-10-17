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

it('copies every _media file into moderation/{topic} and stores the copied paths', function () {
    $api = app(MediaPublicApi::class);
    $a = $api->storePrivate('fanart/1', UploadedFile::fake()->image('a.png', 100, 100));
    $b = $api->storePrivateFile('fanart/1', UploadedFile::fake()->create('b.mp3', 10, 'audio/mpeg'));

    $report = createMediaReport(alice($this), [$a, $b]);

    $media = $report->fresh()->content_snapshot['_media'];
    expect($media)->toHaveCount(2);
    foreach ($media as $path) {
        expect($path)->toStartWith('moderation/mediatest/');
        Storage::disk('private')->assertExists($path);
    }
    expect($media)->not->toContain($a)->not->toContain($b);
    expect($media[0])->toEndWith('.png');
});

it('keeps the copy readable after the source file is removed', function () {
    $api = app(MediaPublicApi::class);
    $a = $api->storePrivate('fanart/1', UploadedFile::fake()->image('a.png', 100, 100));

    $report = createMediaReport(alice($this), [$a]);
    Storage::disk('private')->delete($a);

    $copy = $report->fresh()->content_snapshot['_media'][0];
    expect($api->exists($copy))->toBeTrue();
    expect(Storage::disk('private')->get($copy))->not->toBe('');
});

it('drops a _media path that does not exist at report time', function () {
    $api = app(MediaPublicApi::class);
    $a = $api->storePrivate('fanart/1', UploadedFile::fake()->image('a.png', 100, 100));

    $report = createMediaReport(alice($this), ['fanart/1/missing.png', $a]);

    $media = $report->fresh()->content_snapshot['_media'];
    expect($media)->toHaveCount(1)->and($media[0])->toStartWith('moderation/mediatest/');
});

it('leaves snapshots without _media untouched', function () {
    $user = alice($this);
    $reason = createReason('profile', 'Spam');

    $this->actingAs($user)->postJson('/moderation/report', [
        'topic_key' => 'profile',
        'entity_id' => 123,
        'reason_id' => $reason->id,
    ])->assertOk();

    $snapshot = \App\Domains\Moderation\Private\Models\ModerationReport::first()->content_snapshot;
    expect($snapshot ?? [])->not->toHaveKey('_media');
});
