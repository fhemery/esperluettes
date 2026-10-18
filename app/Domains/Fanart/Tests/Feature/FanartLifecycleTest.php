<?php

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Models\FanartRoleRequest;
use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
});

function lifecycleFanartist(int $storyId, int $userId): void
{
    Fanartist::create(['story_id' => $storyId, 'user_id' => $userId, 'granted_by_user_id' => $userId]);
    FanartRoleRequest::create(['story_id' => $storyId, 'user_id' => $userId, 'status' => 'pending']);
}

it('hard-deletes every fanart and artwork of a deleted user, plus their fanartist rows and requests', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $other = publicStory('Autre', $author->id);
    $mine = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);
    $artwork = createFanart(['story_id' => $story->id, 'user_id' => $artist->id, 'is_artwork' => true]);
    $trashed = createFanart(['story_id' => $other->id, 'user_id' => $artist->id]);
    $trashed->delete();
    $kept = createFanart(['story_id' => $story->id, 'user_id' => $author->id]);
    lifecycleFanartist($story->id, $artist->id);

    event(new UserDeleted($artist->id));

    expect(Fanart::withTrashed()->whereIn('id', [$mine->id, $artwork->id, $trashed->id])->count())->toBe(0)
        ->and(Fanart::find($kept->id))->not->toBeNull()
        ->and(Fanartist::where('user_id', $artist->id)->count())->toBe(0)
        ->and(FanartRoleRequest::where('user_id', $artist->id)->count())->toBe(0)
        ->and(countNotificationsByKey('fanart.removed_with_content'))->toBe(0);
});

it('wipes a co-authored story and all its fanarts when one of its authors deletes their account, notifying each fanartist once', function () {
    $alice = alice($this);
    $bob = bob($this);
    $artist = carol($this);
    $story = publicStory('Partagee', $alice->id);
    addCollaborator($story->id, $bob->id, 'author');
    createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);
    createFanart(['story_id' => $story->id, 'user_id' => $artist->id, 'title' => 'Deux']);
    $bobArtwork = createFanart(['story_id' => $story->id, 'user_id' => $bob->id, 'is_artwork' => true]);
    lifecycleFanartist($story->id, $artist->id);

    deleteUser($this, $alice);

    expect(Story::withTrashed()->find($story->id))->toBeNull()
        ->and(Fanart::withTrashed()->where('story_id', $story->id)->count())->toBe(0)
        ->and(Fanart::withTrashed()->find($bobArtwork->id))->toBeNull()
        ->and(Fanartist::where('story_id', $story->id)->count())->toBe(0)
        ->and(FanartRoleRequest::where('story_id', $story->id)->count())->toBe(0)
        ->and(countNotificationsByKey('fanart.removed_with_content'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.removed_with_content')->id))->toBe([$artist->id]);
});

it('soft-deletes a deactivated fanartist\'s fanarts and restores them on reactivation', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);

    event(new UserDeactivated($artist->id));
    $this->assertSoftDeleted('fanarts', ['id' => $fanart->id]);

    event(new UserReactivated($artist->id));
    $this->assertDatabaseHas('fanarts', ['id' => $fanart->id, 'deleted_at' => null]);
});

it('hides a story\'s fanarts when its sole author is deactivated (story soft-deleted) and shows them back on reactivation', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => carol($this)->id]);
    $policy = app(FanartPolicy::class);

    expect($policy->view($reader->id, $fanart))->toBeTrue();

    deactivateUser($author);
    expect($policy->view($reader->id, $fanart))->toBeFalse();

    app(\App\Domains\Auth\Private\Services\UserService::class)->activateUser($author->fresh());
    expect($policy->view($reader->id, $fanart))->toBeTrue();
});

it('keeps a co-authored story\'s fanarts visible while one of its two authors is deactivated, hides them once both are', function () {
    $alice = alice($this);
    $bob = bob($this);
    $reader = carol($this);
    $story = publicStory('Partagee', $alice->id);
    addCollaborator($story->id, $bob->id, 'author');
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $reader->id]);
    $policy = app(FanartPolicy::class);

    deactivateUser($alice);
    expect($policy->view($reader->id, $fanart))->toBeTrue();

    deactivateUser($bob);
    expect($policy->view($reader->id, $fanart))->toBeFalse();
});

it('deletes all fanarts, fanartists and requests of a deleted story and notifies each fanartist once', function () {
    $author = alice($this);
    $a = bob($this);
    $b = carol($this);
    $story = publicStory('Histoire', $author->id);
    $other = publicStory('Autre', $author->id);
    createFanart(['story_id' => $story->id, 'user_id' => $a->id]);
    createFanart(['story_id' => $story->id, 'user_id' => $a->id, 'title' => 'Deux']);
    createFanart(['story_id' => $story->id, 'user_id' => $b->id]);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'is_artwork' => true]);
    $survivor = createFanart(['story_id' => $other->id, 'user_id' => $a->id]);
    lifecycleFanartist($story->id, $a->id);
    lifecycleFanartist($other->id, $a->id);

    $this->actingAs($author)->delete(route('stories.destroy', $story->slug));

    $targets = [];
    foreach (\Illuminate\Support\Facades\DB::table('notification_reads')->pluck('user_id') as $id) {
        $targets[] = (int) $id;
    }
    sort($targets);
    $expected = [$a->id, $b->id];
    sort($expected);

    expect(Fanart::withTrashed()->where('story_id', $story->id)->count())->toBe(0)
        ->and(Fanart::find($survivor->id))->not->toBeNull()
        ->and(Fanartist::where('story_id', $story->id)->count())->toBe(0)
        ->and(Fanartist::where('story_id', $other->id)->count())->toBe(1)
        ->and(FanartRoleRequest::where('story_id', $story->id)->count())->toBe(0)
        ->and(countNotificationsByKey('fanart.removed_with_content'))->toBe(2)
        ->and($targets)->toBe($expected);
});

it('deletes the fanarts of a deleted chapter and notifies their fanartists, not the authors', function () {
    $author = alice($this);
    $artist = bob($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $keptChapter = createPublishedChapter($this, $story, $author);
    createFanart(['story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => $artist->id]);
    createFanart(['story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => $artist->id, 'title' => 'Deux']);
    createFanart(['story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => $author->id, 'is_artwork' => true]);
    $survivor = createFanart(['story_id' => $story->id, 'chapter_id' => $keptChapter->id, 'user_id' => $artist->id]);

    $this->actingAs($author)->delete(route('chapters.destroy', [$story->slug, $chapter->slug]));

    expect(Chapter::find($chapter->id))->toBeNull()
        ->and(Fanart::withTrashed()->where('chapter_id', $chapter->id)->count())->toBe(0)
        ->and(Fanart::find($survivor->id))->not->toBeNull()
        ->and(countNotificationsByKey('fanart.removed_with_content'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.removed_with_content')->id))->toBe([$artist->id]);
});

it('hides fanarts of an unpublished chapter from readers and shows them again on republish', function () {
    $author = alice($this);
    $reader = bob($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $fanart = createFanart(['story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => carol($this)->id]);
    $policy = app(FanartPolicy::class);

    expect($policy->view($reader->id, $fanart))->toBeTrue();

    $chapter->update(['status' => Chapter::STATUS_NOT_PUBLISHED]);
    expect($policy->view($reader->id, $fanart))->toBeFalse()
        ->and($policy->view($author->id, $fanart))->toBeTrue();

    $chapter->update(['status' => Chapter::STATUS_PUBLISHED]);
    expect($policy->view($reader->id, $fanart))->toBeTrue();
});
