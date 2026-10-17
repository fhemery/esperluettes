<?php

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Http\UploadedFile;

/**
 * Create a fanart row. `file_path` defaults to a path under the story's
 * `fanart/{storyId}` scope with no file behind it; use createPublishedImage()
 * or createPendingAudio() when a real stored file is needed.
 */
function createFanart(array $overrides = []): Fanart
{
    $storyId = $overrides['story_id'] ?? 1;

    return Fanart::create(array_merge([
        'story_id' => $storyId,
        'chapter_id' => null,
        'user_id' => 1,
        'kind' => FanartKind::Image,
        'status' => FanartStatus::Published,
        'is_artwork' => false,
        'title' => 'Un fanart',
        'description' => null,
        'file_path' => 'fanart/' . $storyId . '/' . uniqid('f', true) . '.jpg',
        'share_on_discord' => false,
        'published_at' => now(),
    ], $overrides));
}

/** A published image fanart whose file is really stored on the private disk (with variants). */
function createPublishedImage(array $overrides = []): Fanart
{
    $storyId = $overrides['story_id'] ?? 1;
    $path = app(MediaPublicApi::class)->storePrivate(
        'fanart/' . $storyId,
        UploadedFile::fake()->image('art.jpg', 1200, 800),
        [400, 800],
    );

    return createFanart(array_merge([
        'kind' => FanartKind::Image,
        'status' => FanartStatus::Published,
        'file_path' => $path,
    ], $overrides));
}

/** A pending audio fanart whose mp3 is really stored on the private disk. */
function createPendingAudio(array $overrides = []): Fanart
{
    $storyId = $overrides['story_id'] ?? 1;
    $path = app(MediaPublicApi::class)->storePrivateFile(
        'fanart/' . $storyId,
        UploadedFile::fake()->create('sound.mp3', 100, 'audio/mpeg'),
    );

    return createFanart(array_merge([
        'chapter_id' => 1,
        'kind' => FanartKind::Audio,
        'status' => FanartStatus::Pending,
        'file_path' => $path,
        'published_at' => null,
    ], $overrides));
}
