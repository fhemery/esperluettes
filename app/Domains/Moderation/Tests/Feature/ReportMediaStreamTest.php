<?php

use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('private');
    $mock = Mockery::mock(ProfilePublicApi::class)->shouldIgnoreMissing();
    $mock->shouldReceive('getPublicProfiles')->andReturn([]);
    $mock->shouldReceive('getPublicProfile')->andReturn(null);
    app()->instance(ProfilePublicApi::class, $mock);
});

function mediaReport($t)
{
    $a = app(MediaPublicApi::class)->storePrivate('fanart/1', UploadedFile::fake()->image('a.png', 100, 100));

    return createMediaReport(alice($t), [$a]);
}

it('streams a snapshot media file to a moderator', function () {
    $report = mediaReport($this);

    $response = $this->actingAs(moderator($this))
        ->get(route('moderation.admin.moderation-reports.media', [$report, 0]));

    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('max-age=3600');
});

it('redirects a confirmed user and a guest away from the media route', function () {
    $report = mediaReport($this);
    auth()->logout();
    $url = route('moderation.admin.moderation-reports.media', [$report, 0]);

    $this->actingAs(alice($this))->get($url)->assertRedirect();

    auth()->logout();
    $this->get($url)->assertRedirect();
});

it('returns 404 for an index out of range', function () {
    $report = mediaReport($this);

    $this->actingAs(moderator($this))
        ->get(route('moderation.admin.moderation-reports.media', [$report, 5]))
        ->assertNotFound();
});

it('passes _media_urls to the formatter render() on the admin show page', function () {
    $report = mediaReport($this);
    MediaSnapshotTestFormatter::$rendered = null;

    $this->actingAs(moderator($this))
        ->get(route('moderation.admin.moderation-reports.show', $report))
        ->assertOk();

    expect(MediaSnapshotTestFormatter::$rendered['_media_urls'])
        ->toBe([route('moderation.admin.moderation-reports.media', [$report, 0])]);
});
