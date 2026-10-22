<?php

use App\Domains\Fanart\Private\Services\ModeService;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns the defaults images=yes, audio=designated for an author who never set them', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);

    $service = app(ModeService::class);

    expect($service->effectiveMode($story->id, FanartKind::Image))->toBe(FanartMode::Yes)
        ->and($service->effectiveMode($story->id, FanartKind::Audio))->toBe(FanartMode::Designated);
});

it('returns the most restrictive mode across co-authors', function () {
    $author = alice($this);
    $coAuthor = bob($this);
    $story = publicStory('Histoire', $author->id);
    addCollaborator($story->id, $coAuthor->id);

    setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'designated');
    setSettingsValue($coAuthor->id, 'stories', 'fanart-images-mode', 'no');
    setSettingsValue($coAuthor->id, 'stories', 'fanart-audio-mode', 'yes');

    $service = app(ModeService::class);

    expect($service->effectiveMode($story->id, FanartKind::Image))->toBe(FanartMode::No)
        ->and($service->effectiveMode($story->id, FanartKind::Audio))->toBe(FanartMode::Designated);
});

it('treats a story without authors as no', function () {
    expect(app(ModeService::class)->effectiveMode(999999, FanartKind::Image))->toBe(FanartMode::No);
});
