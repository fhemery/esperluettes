<?php

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserReactivated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('quote notifications', function () {
    it('notifies the chapter author when a reader quotes, not the quoter', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)->postJson('/quotes', [
            'chapter_id' => $chapter->id,
            'story_id' => $story->id,
            'highlighted_text' => 'A passage',
        ])->assertStatus(201);

        expect(countNotificationsByKey('quote.chapter_quoted'))->toBe(1);

        $notification = getLatestNotificationByKey('quote.chapter_quoted');
        $targets = getNotificationTargetUserIds($notification->id);

        expect($targets)->toContain($author->id);
        expect($targets)->not->toContain($reader->id);
    });

    it('notifies the author when a beta reader quotes a private story', function () {
        $author = alice($this);
        $betaReader = bob($this);
        $story = privateStory('Private Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        addCollaborator($story->id, $betaReader->id, 'beta-reader');

        $this->actingAs($betaReader)->postJson('/quotes', [
            'chapter_id' => $chapter->id,
            'story_id' => $story->id,
            'highlighted_text' => 'A private passage',
        ])->assertStatus(201);

        expect(countNotificationsByKey('quote.chapter_quoted'))->toBe(1);

        $notification = getLatestNotificationByKey('quote.chapter_quoted');
        $targets = getNotificationTargetUserIds($notification->id);

        expect($targets)->toContain($author->id);
        expect($targets)->not->toContain($betaReader->id);
    });

    it('does not emit a notification on note update or delete', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $created = $this->actingAs($reader)->postJson('/quotes', [
            'chapter_id' => $chapter->id,
            'story_id' => $story->id,
            'highlighted_text' => 'A passage',
        ])->assertStatus(201)->json();

        expect(countNotificationsByKey('quote.chapter_quoted'))->toBe(1);

        $this->actingAs($reader)->putJson('/quotes/' . $created['id'], ['note' => 'updated'])->assertOk();
        $this->actingAs($reader)->deleteJson('/quotes/' . $created['id'])->assertNoContent();

        expect(countNotificationsByKey('quote.chapter_quoted'))->toBe(1);
    });
});

describe('user lifecycle cascades', function () {
    it('soft-deletes quotes on owner deactivation and restores on reactivation', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        event(new UserDeactivated($reader->id));
        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);

        event(new UserReactivated($reader->id));
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'deleted_at' => null]);
    });

    it('deletes the quotes when the owner is deleted', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        event(new UserDeleted($reader->id));

        $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
    });

    it('also deletes quotes already soft-deleted by a prior deactivation', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        event(new UserDeactivated($reader->id));
        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);

        event(new UserDeleted($reader->id));

        $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
    });
});
