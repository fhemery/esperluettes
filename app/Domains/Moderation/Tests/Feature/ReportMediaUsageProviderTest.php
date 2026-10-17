<?php

use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use App\Domains\Moderation\Private\Models\ModerationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('private');
});

function claimedPaths(): array
{
    return array_keys(app(MediaUsageRegistry::class)->liveSet());
}

it('claims the _media paths of every report', function () {
    $api = app(\App\Domains\Media\Public\Api\MediaPublicApi::class);
    $a = $api->storePrivate('fanart/1', UploadedFile::fake()->image('a.png', 100, 100));
    $b = $api->storePrivate('fanart/1', UploadedFile::fake()->image('b.png', 100, 100));

    $r1 = createMediaReport(alice($this), [$a]);
    $r2 = createMediaReport(alice($this), [$b]);

    expect(claimedPaths())->toContain($r1->fresh()->content_snapshot['_media'][0])
        ->toContain($r2->fresh()->content_snapshot['_media'][0]);
});

it('stops claiming them once the report is deleted', function () {
    $api = app(\App\Domains\Media\Public\Api\MediaPublicApi::class);
    $a = $api->storePrivate('fanart/1', UploadedFile::fake()->image('a.png', 100, 100));
    $report = createMediaReport(alice($this), [$a]);
    $copy = $report->fresh()->content_snapshot['_media'][0];

    ModerationReport::whereKey($report->id)->delete();

    expect(claimedPaths())->not->toContain($copy);
});
