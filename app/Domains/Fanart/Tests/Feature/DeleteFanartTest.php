<?php

use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Support\FanartStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
});

it('lets a fanartist delete their pending and published fanarts without notifying anyone', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $pending = createFanart(['story_id' => $story->id, 'user_id' => $artist->id, 'status' => FanartStatus::Pending, 'published_at' => null]);
    $published = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);

    $this->actingAs($artist)->delete(route('fanart.destroy', $pending->id))
        ->assertRedirect()->assertSessionHas('success');
    $this->actingAs($artist)->delete(route('fanart.destroy', $published->id))
        ->assertRedirect()->assertSessionHas('success');

    expect(Fanart::withTrashed()->whereIn('id', [$pending->id, $published->id])->count())->toBe(0)
        ->and(countNotificationsByKey('fanart.deleted'))->toBe(0)
        ->and(countNotificationsByKey('fanart.deleted_by_moderation'))->toBe(0);
});

it('lets an author delete a published fanart and notifies the fanartist', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);

    $this->actingAs($author)->delete(route('fanart.destroy', $fanart->id))->assertRedirect();

    expect(Fanart::withTrashed()->find($fanart->id))->toBeNull()
        ->and(countNotificationsByKey('fanart.deleted'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.deleted')->id))->toBe([$artist->id]);
});

it('returns 403 to a reader and to another fanartist', function () {
    $author = alice($this);
    $artist = bob($this);
    $other = carol($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);

    $this->actingAs($other)->delete(route('fanart.destroy', $fanart->id))->assertForbidden();
    expect(Fanart::find($fanart->id))->not->toBeNull();
});

it('lets a moderator delete any fanart, notifies authors and fanartist, emits Fanart.DeletedByModeration', function () {
    $author = alice($this);
    $artist = bob($this);
    $moderator = moderator($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createFanart([
        'story_id' => $story->id, 'user_id' => $artist->id,
        'status' => FanartStatus::Pending, 'published_at' => null,
    ]);

    $this->actingAs($moderator)->delete(route('fanart.moderation.destroy', $fanart->id))
        ->assertRedirect()->assertSessionHas('success');

    $targets = getNotificationTargetUserIds(getLatestNotificationByKey('fanart.deleted_by_moderation')->id);
    sort($targets);
    $expected = [$author->id, $artist->id];
    sort($expected);

    expect(Fanart::withTrashed()->find($fanart->id))->toBeNull()
        ->and($targets)->toBe($expected)
        ->and(app(EventPublicApi::class)->latest('Fanart.DeletedByModeration'))->not->toBeNull();
});

it('returns 403 on the moderation route to an author', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);

    $this->actingAs($author)->delete(route('fanart.moderation.destroy', $fanart->id))->assertForbidden();
    expect(Fanart::find($fanart->id))->not->toBeNull();
});

it('writes the moderation deletion to the audit log', function () {
    $author = alice($this);
    $artist = bob($this);
    $moderator = moderator($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);

    $this->actingAs($moderator)->delete(route('fanart.moderation.destroy', $fanart->id));

    app()->setLocale('fr');
    $event = collect(app(EventPublicApi::class)->list())
        ->first(fn ($e) => $e->name() === 'Fanart.DeletedByModeration');

    expect($event)->not->toBeNull()
        ->and($event->payload())->toBe([
            'fanartId' => $fanart->id,
            'storyId' => $story->id,
            'userId' => $artist->id,
            'moderatorId' => $moderator->id,
        ])
        ->and($event->triggeredByUserId())->toBe($moderator->id)
        ->and($event->summary())->toContain((string) $fanart->id);
});
