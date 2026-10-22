<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
});

function pendingImage(array $overrides = []): Fanart
{
    return createFanart(array_merge([
        'status' => FanartStatus::Pending,
        'published_at' => null,
    ], $overrides));
}

function latestHtml(string $type): string
{
    app()->setLocale('fr');
    $notification = getLatestNotificationByKey($type);
    expect($notification)->not->toBeNull();

    return app(NotificationPublicApi::class)->getNotificationsByIds([$notification->id])[$notification->id]->htmlDisplay;
}

it('publishes a pending image on accept and notifies the submitter with the comment', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = pendingImage(['story_id' => $story->id, 'user_id' => $artist->id, 'title' => 'Portrait']);

    $this->actingAs($author)
        ->from('/retour')
        ->post(route('fanart.accept', $fanart->id), ['comment' => 'Superbe travail'])
        ->assertRedirect('/retour')
        ->assertSessionHas('success');

    $fresh = $fanart->fresh();
    expect($fresh->status)->toBe(FanartStatus::Published)
        ->and($fresh->published_at)->not->toBeNull()
        ->and(countNotificationsByKey('fanart.accepted'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.accepted')->id))->toBe([$artist->id])
        ->and(latestHtml('fanart.accepted'))->toContain('Superbe travail');
});

it('stores the comment only in the notification payload', function () {
    expect(Schema::hasColumn('fanarts', 'comment'))->toBeFalse()
        ->and(Schema::hasColumn('fanarts', 'review_comment'))->toBeFalse();
});

it('deletes a pending fanart on decline and notifies the submitter with the comment', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = pendingImage(['story_id' => $story->id, 'user_id' => $artist->id, 'title' => 'Portrait']);

    $this->actingAs($author)
        ->post(route('fanart.decline', $fanart->id), ['comment' => 'Pas pour cette histoire'])
        ->assertRedirect();

    expect(Fanart::withTrashed()->find($fanart->id))->toBeNull()
        ->and(countNotificationsByKey('fanart.declined'))->toBe(1)
        ->and(latestHtml('fanart.declined'))->toContain('Pas pour cette histoire');
});

it('redirects a decline back without the deleted fanart id', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = pendingImage(['story_id' => $story->id, 'user_id' => bob($this)->id]);

    $this->actingAs($author)
        ->from('/stories/histoire?tab=fanarts&fanart=' . $fanart->id)
        ->post(route('fanart.decline', $fanart->id), ['comment' => 'Non'])
        ->assertRedirect('/stories/histoire?tab=fanarts');
});

it('requires a comment of at most 500 chars', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = pendingImage(['story_id' => $story->id, 'user_id' => bob($this)->id]);
    $this->actingAs($author);

    foreach (['accept', 'decline'] as $action) {
        $this->post(route("fanart.$action", $fanart->id), [])->assertSessionHasErrors('comment');
        $this->post(route("fanart.$action", $fanart->id), ['comment' => str_repeat('a', 501)])
            ->assertSessionHasErrors('comment');
    }

    expect($fanart->fresh()->status)->toBe(FanartStatus::Pending);

    $this->post(route('fanart.accept', $fanart->id), ['comment' => str_repeat('a', 500)])
        ->assertSessionHasNoErrors();
    expect($fanart->fresh()->status)->toBe(FanartStatus::Published);
});

it('lets a co-author decide; the second author then gets an already-decided response', function () {
    $author = alice($this);
    $coAuthor = carol($this);
    $story = publicStory('Histoire', $author->id);
    addCollaborator($story->id, $coAuthor->id);
    $accepted = pendingImage(['story_id' => $story->id, 'user_id' => bob($this)->id]);
    $declined = pendingImage(['story_id' => $story->id, 'user_id' => bob($this)->id]);

    $this->actingAs($coAuthor)->post(route('fanart.accept', $accepted->id), ['comment' => 'Oui'])
        ->assertSessionHas('success');
    $this->actingAs($coAuthor)->post(route('fanart.decline', $declined->id), ['comment' => 'Non']);

    $this->actingAs($author)->post(route('fanart.decline', $accepted->id), ['comment' => 'Non'])
        ->assertRedirect()->assertSessionHas('error');
    $this->post(route('fanart.accept', $declined->id), ['comment' => 'Oui'])
        ->assertRedirect()->assertSessionHas('error');

    expect($accepted->fresh()->status)->toBe(FanartStatus::Published)
        ->and(Fanart::find($declined->id))->toBeNull();
});

it('replaces the chapter\'s published audio on audio accept and notifies its submitter', function () {
    $author = alice($this);
    $oldArtist = bob($this);
    $newArtist = carol($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $old = createPendingAudio([
        'story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => $oldArtist->id,
        'status' => FanartStatus::Published, 'published_at' => now(),
    ]);
    $new = createPendingAudio(['story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => $newArtist->id]);

    $this->actingAs($author)
        ->post(route('fanart.accept', $new->id), ['comment' => 'Belle voix'])
        ->assertSessionHas('success');

    expect(Fanart::withTrashed()->find($old->id))->toBeNull()
        ->and($new->fresh()->status)->toBe(FanartStatus::Published)
        ->and(Fanart::where('chapter_id', $chapter->id)->where('kind', FanartKind::Audio)
            ->where('status', FanartStatus::Published)->count())->toBe(1)
        ->and(countNotificationsByKey('fanart.audio_replaced'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.audio_replaced')->id))
        ->toBe([$oldArtist->id]);
});

it('keeps other pending audios on the same chapter pending', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $first = createPendingAudio(['story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => bob($this)->id]);
    $second = createPendingAudio(['story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => carol($this)->id]);

    $this->actingAs($author)->post(route('fanart.accept', $first->id), ['comment' => 'Ok']);

    expect($first->fresh()->status)->toBe(FanartStatus::Published)
        ->and($second->fresh()->status)->toBe(FanartStatus::Pending)
        ->and(countNotificationsByKey('fanart.audio_replaced'))->toBe(0);

    // Accepting the second one later replaces the first: still at most one published audio.
    $this->post(route('fanart.accept', $second->id), ['comment' => 'Ok']);

    expect(Fanart::find($first->id))->toBeNull()
        ->and(Fanart::where('chapter_id', $chapter->id)->where('status', FanartStatus::Published)->count())->toBe(1);
});

it('does not notify the actor when they replace their own audio', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    createPendingAudio([
        'story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => $author->id,
        'status' => FanartStatus::Published, 'published_at' => now(),
    ]);
    $new = createPendingAudio(['story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => bob($this)->id]);

    $this->actingAs($author)->post(route('fanart.accept', $new->id), ['comment' => 'Ok']);

    expect(countNotificationsByKey('fanart.audio_replaced'))->toBe(0);
});

it('returns 403 to the submitter, a reader, a fanartiste and a moderator', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = pendingImage(['story_id' => $story->id, 'user_id' => $artist->id]);
    $fanartiste = carol($this);
    Fanartist::create(['story_id' => $story->id, 'user_id' => $fanartiste->id, 'granted_by_user_id' => $author->id]);

    foreach ([$artist, $fanartiste, daniel($this), moderator($this)] as $user) {
        $this->actingAs($user);
        $this->post(route('fanart.accept', $fanart->id), ['comment' => 'Ok'])->assertForbidden();
        $this->post(route('fanart.decline', $fanart->id), ['comment' => 'Non'])->assertForbidden();
    }

    expect($fanart->fresh()->status)->toBe(FanartStatus::Pending);
});

it('returns 403 for a non-author on an already published fanart', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $artist = bob($this);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);

    $this->actingAs($artist)
        ->post(route('fanart.decline', $fanart->id), ['comment' => 'Non'])
        ->assertForbidden();
    expect(Fanart::find($fanart->id))->not->toBeNull();
});

it('redirects a guest and a non-confirmed user', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = pendingImage(['story_id' => $story->id, 'user_id' => bob($this)->id]);

    $this->post(route('fanart.accept', $fanart->id), ['comment' => 'Ok'])->assertRedirect(route('login'));
    $this->actingAs(carol($this, roles: [Roles::USER]))
        ->post(route('fanart.accept', $fanart->id), ['comment' => 'Ok'])->assertRedirect();

    expect($fanart->fresh()->status)->toBe(FanartStatus::Pending);
});

it('still allows the decision when the mode is now no / the story is now private (spec 4.7.8)', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = pendingImage(['story_id' => $story->id, 'user_id' => bob($this)->id]);
    $other = pendingImage(['story_id' => $story->id, 'user_id' => bob($this)->id]);

    setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'no');
    setSettingsValue($author->id, 'stories', 'fanart-audio-mode', 'no');

    $this->actingAs($author)->post(route('fanart.accept', $fanart->id), ['comment' => 'Ok'])
        ->assertSessionHas('success');

    Story::whereKey($story->id)->update(['visibility' => Story::VIS_PRIVATE]);

    $this->post(route('fanart.decline', $other->id), ['comment' => 'Non'])->assertSessionHas('success');

    expect($fanart->fresh()->status)->toBe(FanartStatus::Published)
        ->and(Fanart::find($other->id))->toBeNull();
});
