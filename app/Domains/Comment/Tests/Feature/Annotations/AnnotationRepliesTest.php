<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Annotation replies — list', function () {
    beforeEach(function () {
        Cache::flush();

        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
        $this->reader = bob($this);
        $this->coAuthor = carol($this);
        addCollaborator($this->story->id, $this->coAuthor->id, 'author');
        // Deactivating or deleting a current author takes the story (and the comment) down with it,
        // so a writer whose status changes under a live chapter is a former co-author: seeded rows only.
        $this->formerCoAuthor = daniel($this);

        $this->actingAs($this->reader);
        $this->from('/chapters/whatever#comments')->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(['body' => '<p>Premier avis</p>', 'highlighted_text' => 'premier passage']),
            annotationItem(['body' => '<p>Second avis</p>', 'highlighted_text' => 'second passage']),
        ]))->assertSessionHasNoErrors();
        auth()->logout();

        $this->comment = Comment::query()->sole();
        [$this->first, $this->second] = CommentAnnotation::query()->orderBy('id')->get()->all();
        $this->url = '/comments/' . $this->comment->id . '/annotations';

        $this->reply = function (CommentAnnotation $root, int $writerId, string $body, string $at = '2026-01-01 10:00:00'): CommentAnnotation {
            return CommentAnnotation::query()->forceCreate([
                'comment_id' => $root->comment_id,
                'parent_annotation_id' => $root->id,
                'author_id' => $writerId,
                'body' => $body,
                'highlighted_text' => '',
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        };
        $this->setActive = function (int $userId, bool $active) {
            $this->actingAs(admin($this));
            $active
                ? app(AuthPublicApi::class)->activateUserById($userId)
                : app(AuthPublicApi::class)->deactivateUserById($userId);
            auth()->logout();
        };
    });

    it('returns replies oldest first under their root for the commenter, an author and a moderator', function () {
        $later = ($this->reply)($this->first, $this->author->id, '<p>Plus tard</p>', '2026-01-02 10:00:00');
        $earlier = ($this->reply)($this->first, $this->coAuthor->id, '<p>Plus tôt</p>', '2026-01-01 10:00:00');

        foreach ([$this->reader, $this->author, moderator($this)] as $viewer) {
            $response = $this->actingAs($viewer)->getJson($this->url)->assertOk();

            $response->assertJsonCount(2, 'items');
            $response->assertJsonCount(2, 'items.0.replies');
            $response->assertJsonCount(0, 'items.1.replies');
            expect($response->json('items.0.replies.0.id'))->toBe($earlier->id)
                ->and($response->json('items.0.replies.1.id'))->toBe($later->id);

            $reply = $response->json('items.0.replies.0');
            expect($reply['parent_annotation_id'])->toBe($this->first->id)
                ->and($reply['author_id'])->toBe($this->coAuthor->id)
                ->and($reply['author_profile']['user_id'])->toBe($this->coAuthor->id)
                ->and($reply['body'])->toContain('Plus tôt')
                ->and($reply['highlighted_text'])->toBe('')
                ->and($reply['is_processed'])->toBeNull()
                ->and($reply['replies'])->toBe([])
                ->and($reply['can_mark_as_processed'])->toBeFalse()
                ->and($reply['can_edit'])->toBeFalse()
                ->and($reply['can_reply'])->toBeFalse();
        }
    });

    it('shows the commenter the author replies under their own roots', function () {
        ($this->reply)($this->second, $this->author->id, '<p>Merci beaucoup</p>');

        $response = $this->actingAs($this->reader)->getJson($this->url)->assertOk();

        $response->assertJsonPath('viewer_role', 'commenter');
        $response->assertJsonCount(1, 'items.1.replies');
        $response->assertJsonPath('items.1.replies.0.author_id', $this->author->id);
        expect($response->json('items.1.replies.0.body'))->toContain('Merci beaucoup');
    });

    it('hides a reply whose writer is deactivated and shows it again after reactivation', function () {
        $reply = ($this->reply)($this->first, $this->formerCoAuthor->id, '<p>Réponse cachée</p>');
        $before = $reply->fresh()->getAttributes();

        ($this->setActive)($this->formerCoAuthor->id, false);

        foreach ([$this->reader, $this->author, moderator($this)] as $viewer) {
            $response = $this->actingAs($viewer)->getJson($this->url)->assertOk();
            $response->assertJsonCount(0, 'items.0.replies');
            expect($response->getContent())->not->toContain('Réponse cachée');
        }
        expect($reply->fresh()->getAttributes())->toBe($before);

        ($this->setActive)($this->formerCoAuthor->id, true);

        $this->actingAs($this->author)->getJson($this->url)
            ->assertOk()
            ->assertJsonCount(1, 'items.0.replies')
            ->assertJsonPath('items.0.replies.0.id', $reply->id);
        expect($reply->fresh()->getAttributes())->toBe($before);
    });

    it('keeps the reply of a deleted user, anonymised', function () {
        $reply = ($this->reply)($this->first, $this->formerCoAuthor->id, '<p>Réponse orpheline</p>');

        deleteUser($this, $this->formerCoAuthor);

        $this->actingAs($this->author)->getJson($this->url)
            ->assertOk()
            ->assertJsonCount(1, 'items.0.replies')
            ->assertJsonPath('items.0.replies.0.id', $reply->id)
            ->assertJsonPath('items.0.replies.0.author_id', null);
    });

    it('sets can_edit only for the commenter on their own roots', function () {
        $this->actingAs($this->reader)->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('items.0.can_edit', true)
            ->assertJsonPath('items.1.can_edit', true);

        foreach ([$this->author, $this->coAuthor, moderator($this)] as $viewer) {
            $this->actingAs($viewer)->getJson($this->url)
                ->assertOk()
                ->assertJsonPath('items.0.can_edit', false)
                ->assertJsonPath('items.1.can_edit', false);
        }
    });

    it('sets can_reply for an author on every root, for the commenter only after an author reply, never for a moderator', function () {
        foreach ([$this->author, $this->coAuthor] as $viewer) {
            $this->actingAs($viewer)->getJson($this->url)
                ->assertOk()
                ->assertJsonPath('items.0.can_reply', true)
                ->assertJsonPath('items.1.can_reply', true);
        }

        $this->actingAs($this->reader)->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('items.0.can_reply', false)
            ->assertJsonPath('items.1.can_reply', false);

        // The commenter's own reply does not unlock them.
        ($this->reply)($this->second, $this->reader->id, '<p>Moi encore</p>');
        ($this->reply)($this->first, $this->author->id, '<p>Réponse auteur</p>');

        $this->actingAs($this->reader)->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('items.0.can_reply', true)
            ->assertJsonPath('items.1.can_reply', false);

        $this->actingAs(moderator($this))->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('items.0.can_reply', false)
            ->assertJsonPath('items.1.can_reply', false);
    });

    it('re-locks the commenter when the only author reply is from a deactivated writer', function () {
        ($this->reply)($this->first, $this->formerCoAuthor->id, '<p>Réponse co-auteur</p>');

        $this->actingAs($this->reader)->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('items.0.can_reply', true);

        ($this->setActive)($this->formerCoAuthor->id, false);

        $this->actingAs($this->reader)->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('items.0.can_reply', false);
    });

    it('sets can_delete on a reply for its writer and for moderators only', function () {
        ($this->reply)($this->first, $this->coAuthor->id, '<p>Réponse co-auteur</p>');

        $this->actingAs($this->coAuthor)->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('items.0.replies.0.can_delete', true)
            ->assertJsonPath('items.0.can_delete', false);

        foreach ([$this->author, $this->reader] as $viewer) {
            $this->actingAs($viewer)->getJson($this->url)
                ->assertOk()
                ->assertJsonPath('items.0.replies.0.can_delete', false);
        }

        $this->actingAs(moderator($this))->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('items.0.replies.0.can_delete', true)
            ->assertJsonPath('items.0.can_delete', true);
    });

    it('fetches writer statuses with a single auth call', function () {
        ($this->reply)($this->first, $this->author->id, '<p>Un</p>');
        ($this->reply)($this->first, $this->coAuthor->id, '<p>Deux</p>');
        ($this->reply)($this->second, $this->author->id, '<p>Trois</p>');

        $this->actingAs($this->author);
        DB::enableQueryLog();
        $this->getJson($this->url)->assertOk()->assertJsonCount(2, 'items.0.replies');
        $statusQueries = collect(DB::getQueryLog())
            ->filter(fn (array $q) => str_contains($q['query'], 'is_active') && str_contains($q['query'], 'from "users"')
                || str_contains($q['query'], 'is_active') && str_contains($q['query'], 'from `users`'))
            ->count();
        DB::disableQueryLog();

        expect($statusQueries)->toBe(1);
    });
});

describe('Annotation replies — write', function () {
    beforeEach(function () {
        Cache::flush();

        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
        $this->reader = bob($this);
        $this->coAuthor = carol($this);
        addCollaborator($this->story->id, $this->coAuthor->id, 'author');

        $this->actingAs($this->reader);
        $this->from('/chapters/whatever#comments')->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(['body' => '<p>Premier avis</p>', 'highlighted_text' => 'premier passage']),
            annotationItem(['body' => '<p>Second avis</p>', 'highlighted_text' => 'second passage']),
        ]))->assertSessionHasNoErrors();
        auth()->logout();

        $this->comment = Comment::query()->sole();
        [$this->first, $this->second] = CommentAnnotation::query()->orderBy('id')->get()->all();
        $this->replyUrl = fn (int $annotationId) => '/comments/annotations/' . $annotationId . '/replies';
        $this->deleteUrl = fn (int $replyId) => '/comments/annotations/replies/' . $replyId;
        $this->postReply = fn ($user, int $annotationId, string $body = '<p>Merci pour la remarque</p>') => $this->actingAs($user)
            ->postJson(($this->replyUrl)($annotationId), ['body' => $body]);
    });

    it('lets the chapter author and a co-author reply to any root annotation', function () {
        $response = ($this->postReply)($this->author, $this->first->id, '<p>Bien vu</p>')->assertCreated();

        $reply = CommentAnnotation::query()->repliesOnly()->sole();
        expect($reply->parent_annotation_id)->toBe($this->first->id)
            ->and((int) $reply->comment_id)->toBe($this->comment->id)
            ->and((int) $reply->author_id)->toBe($this->author->id)
            ->and($reply->highlighted_text)->toBeNull()
            ->and($reply->prefix)->toBeNull()
            ->and($reply->suffix)->toBeNull()
            ->and($reply->body)->toContain('Bien vu');

        $response->assertJsonPath('id', $reply->id)
            ->assertJsonPath('parent_annotation_id', $this->first->id)
            ->assertJsonPath('author_id', $this->author->id)
            ->assertJsonPath('author_profile.user_id', $this->author->id)
            ->assertJsonPath('highlighted_text', '')
            ->assertJsonPath('is_processed', null)
            ->assertJsonPath('replies', [])
            ->assertJsonPath('can_delete', true)
            ->assertJsonPath('can_edit', false)
            ->assertJsonPath('can_reply', false)
            ->assertJsonPath('can_mark_as_processed', false);

        ($this->postReply)($this->coAuthor, $this->second->id)->assertCreated();

        expect(CommentAnnotation::query()->repliesOnly()->count())->toBe(2);
    });

    it('sanitizes the reply body', function () {
        ($this->postReply)($this->author, $this->first->id, '<p>Ok<script>alert(1)</script></p>')->assertCreated();

        expect(CommentAnnotation::query()->repliesOnly()->sole()->body)->not->toContain('script');
    });

    it('refuses a beta reader with 403', function () {
        $beta = daniel($this);
        addCollaborator($this->story->id, $beta->id, 'beta-reader');

        ($this->postReply)($beta, $this->first->id)->assertForbidden();

        expect(CommentAnnotation::query()->repliesOnly()->count())->toBe(0);
    });

    it('refuses the commenter before any author reply and accepts after one', function () {
        ($this->postReply)($this->reader, $this->first->id)->assertForbidden();

        ($this->postReply)($this->author, $this->first->id)->assertCreated();

        ($this->postReply)($this->reader, $this->first->id, '<p>Merci à vous</p>')->assertCreated()
            ->assertJsonPath('author_id', $this->reader->id)
            ->assertJsonPath('can_delete', true);
        // An author reply under one root does not unlock the other.
        ($this->postReply)($this->reader, $this->second->id)->assertForbidden();
    });

    it('refuses a moderator with 403', function () {
        ($this->postReply)(moderator($this), $this->first->id)->assertForbidden();
        ($this->postReply)(admin($this), $this->first->id)->assertForbidden();

        expect(CommentAnnotation::query()->repliesOnly()->count())->toBe(0);
    });

    it('refuses another reader with 403', function () {
        $other = alice($this, ['name' => 'Eve', 'email' => 'eve@example.com']);
        ($this->postReply)($this->author, $this->first->id)->assertCreated();

        ($this->postReply)($other, $this->first->id)->assertForbidden();

        expect(CommentAnnotation::query()->repliesOnly()->count())->toBe(1);
    });

    it('refuses a reply to a reply with 422', function () {
        $replyId = ($this->postReply)($this->author, $this->first->id)->assertCreated()->json('id');

        ($this->postReply)($this->author, $replyId)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body' => __('comment::annotations.errors.reply_to_reply')]);

        expect(CommentAnnotation::query()->repliesOnly()->count())->toBe(1);
    });

    it('refuses an empty body and a body over 1000 characters with 422', function () {
        ($this->postReply)($this->author, $this->first->id, '<p>   </p>')
            ->assertUnprocessable()->assertJsonValidationErrors('body');
        ($this->postReply)($this->author, $this->first->id, '<p>' . generateDummyText(1001) . '</p>')
            ->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->actingAs($this->author)->postJson(($this->replyUrl)($this->first->id), [])
            ->assertUnprocessable()->assertJsonValidationErrors('body');

        ($this->postReply)($this->author, $this->first->id, '<p>' . generateDummyText(1000) . '</p>')->assertCreated();
        expect(CommentAnnotation::query()->repliesOnly()->count())->toBe(1);
    });

    it('returns 404 for a deleted root annotation', function () {
        $this->actingAs(moderator($this))->deleteJson('/comments/annotations/' . $this->first->id)->assertNoContent();

        ($this->postReply)($this->author, $this->first->id)->assertNotFound();
        ($this->postReply)($this->author, 999999)->assertNotFound();
    });

    it('lets the writer delete their own reply', function () {
        $replyId = ($this->postReply)($this->coAuthor, $this->first->id)->assertCreated()->json('id');
        $sibling = ($this->postReply)($this->author, $this->first->id)->assertCreated()->json('id');

        $this->actingAs($this->coAuthor)->deleteJson(($this->deleteUrl)($replyId))->assertNoContent();

        expect(CommentAnnotation::withTrashed()->find($replyId)->trashed())->toBeTrue()
            ->and(CommentAnnotation::query()->find($sibling))->not->toBeNull()
            ->and($this->first->fresh()->trashed())->toBeFalse();

        $this->actingAs($this->coAuthor)->deleteJson(($this->deleteUrl)($replyId))->assertNotFound();
    });

    it('refuses deleting another user’s reply, and a root annotation through the reply route, with 403', function () {
        $replyId = ($this->postReply)($this->author, $this->first->id)->assertCreated()->json('id');

        foreach ([$this->coAuthor, $this->reader, moderator($this)] as $user) {
            $this->actingAs($user)->deleteJson(($this->deleteUrl)($replyId))->assertForbidden();
        }
        $this->actingAs($this->reader)->deleteJson(($this->deleteUrl)($this->first->id))->assertForbidden();

        expect(CommentAnnotation::query()->find($replyId))->not->toBeNull()
            ->and($this->first->fresh()->trashed())->toBeFalse();
    });

    it('lets a moderator delete a reply through the existing route without touching siblings', function () {
        $replyId = ($this->postReply)($this->author, $this->first->id)->assertCreated()->json('id');
        $sibling = ($this->postReply)($this->coAuthor, $this->first->id)->assertCreated()->json('id');

        $this->actingAs(moderator($this))->deleteJson('/comments/annotations/' . $replyId)->assertNoContent();

        expect(CommentAnnotation::withTrashed()->find($replyId)->trashed())->toBeTrue()
            ->and(CommentAnnotation::query()->find($sibling))->not->toBeNull()
            ->and($this->first->fresh()->trashed())->toBeFalse();
    });

    it('dispatches no notification and no event', function () {
        Notification::fake();
        $events = DB::table('events_domain')->count();
        $notifications = DB::table('notifications')->count();

        $replyId = ($this->postReply)($this->author, $this->first->id)->assertCreated()->json('id');
        ($this->postReply)($this->reader, $this->first->id)->assertCreated();
        $this->actingAs($this->author)->deleteJson(($this->deleteUrl)($replyId))->assertNoContent();

        Notification::assertNothingSent();
        expect(DB::table('events_domain')->count())->toBe($events)
            ->and(DB::table('notifications')->count())->toBe($notifications);
    });
});
