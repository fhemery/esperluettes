<?php

use App\Domains\Fanart\Private\Support\FanartMediaUsageProvider;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Media\Private\Services\MediaService;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
});

it('claims file_path of published, pending and soft-deleted fanarts', function () {
    $published = createFanart(['status' => FanartStatus::Published]);
    $pending = createFanart(['status' => FanartStatus::Pending]);
    $trashed = createFanart();
    $trashed->delete();

    $paths = iterator_to_array((new FanartMediaUsageProvider())->usedPaths(), false);

    expect($paths)->toEqualCanonicalizing([$published->file_path, $pending->file_path, $trashed->file_path]);
});

it('is registered on the media usage registry at boot', function () {
    $registered = collect(app(MediaUsageRegistry::class)->providers())
        ->contains(fn ($p) => $p instanceof FanartMediaUsageProvider);

    expect($registered)->toBeTrue();
});

it('keeps a claimed fanart file and its variants through media:gc', function () {
    $kept = createPublishedImage(['story_id' => 5]);
    $keptSoftDeleted = createPublishedImage(['story_id' => 5]);
    $keptSoftDeleted->delete();
    $orphan = app(\App\Domains\Media\Public\Api\MediaPublicApi::class)->storePrivate(
        'fanart/5',
        \Illuminate\Http\UploadedFile::fake()->image('orphan.jpg', 1200, 800),
        [400, 800],
    );
    $orphanStem = pathinfo($orphan, PATHINFO_FILENAME);
    $keptStem = pathinfo($kept->file_path, PATHINFO_FILENAME);

    $report = app(MediaService::class)->gc(-1);

    expect($report['deleted'])->toBe([$orphan]);
    Storage::disk('private')->assertExists($kept->file_path);
    Storage::disk('private')->assertExists("fanart/5/{$keptStem}-400w.webp");
    Storage::disk('private')->assertExists($keptSoftDeleted->file_path);
    Storage::disk('private')->assertMissing($orphan);
    Storage::disk('private')->assertMissing("fanart/5/{$orphanStem}-400w.webp");
});
