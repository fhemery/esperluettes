<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
