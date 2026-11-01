<?php

use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;
use App\Domains\Story\Public\Events\StoryDeleted;

it('round-trips deletedByUserId on ChapterDeleted and tolerates its absence', function () {
    $chapter = new ChapterSnapshot(1, 'T', 'slug', 1, 'published', 0, 0);
    $event = new ChapterDeleted(2, $chapter, 7);

    expect(ChapterDeleted::fromPayload($event->toPayload())->deletedByUserId)->toBe(7);

    $payload = (new ChapterDeleted(2, $chapter))->toPayload();
    unset($payload['deletedByUserId']);
    expect(ChapterDeleted::fromPayload($payload)->deletedByUserId)->toBeNull();
});

it('round-trips deletedByUserId on StoryDeleted and tolerates its absence', function () {
    $payload = ['story' => [], 'chapters' => [], 'deletedByUserId' => 7];
    expect(StoryDeleted::fromPayload($payload)->deletedByUserId)->toBe(7);

    unset($payload['deletedByUserId']);
    expect(StoryDeleted::fromPayload($payload)->deletedByUserId)->toBeNull();
});
