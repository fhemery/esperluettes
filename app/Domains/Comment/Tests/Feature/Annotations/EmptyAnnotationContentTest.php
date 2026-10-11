<?php

use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Events\Public\Api\EventPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('POST /comments/annotations/{annotationId}/empty-content', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
        $this->reader = bob($this);

        $this->actingAs($this->reader);
        $this->from('/chapters/whatever#comments')->post('/comments', [
            'entity_type' => 'chapter',
            'entity_id' => $this->chapter->id,
            'body' => generateDummyText(140),
            'annotations' => json_encode([
                ['body' => '<p>Premier avis</p>', 'highlighted_text' => 'premier passage', 'prefix' => 'avant ', 'suffix' => ' après'],
            ]),
        ])->assertSessionHasNoErrors();
        auth()->logout();

        $this->comment = Comment::query()->sole();
        $this->root = CommentAnnotation::query()->sole();
        $this->url = '/comments/annotations/' . $this->root->id . '/empty-content';
        $this->placeholder = e(__('comment::annotations.emptied_body'));
    });

    it('lets a moderator, an admin and a tech-admin empty a root annotation', function () {
        foreach (['moderator', 'admin', 'techAdmin'] as $factory) {
            $this->root->update(['body' => '<p>Premier avis</p>', 'content_emptied_at' => null]);
            $user = $factory($this);

            $this->actingAs($user)->postJson($this->url)
                ->assertOk()
                ->assertJsonPath('annotation.id', $this->root->id)
                ->assertJsonPath('annotation.body', $this->placeholder)
                ->assertJsonPath('annotation.is_emptied', true);

            $fresh = $this->root->fresh();
            expect($fresh->body)->toBe($this->placeholder)
                ->and($fresh->content_emptied_at)->not->toBeNull();
        }
    });

    it('keeps the anchor, the replies and the processed flag', function () {
        $reply = CommentAnnotation::query()->create([
            'comment_id' => $this->comment->id,
            'parent_annotation_id' => $this->root->id,
            'author_id' => $this->author->id,
            'body' => '<p>Réponse</p>',
        ]);
        $this->root->update(['is_processed' => true, 'processed_at' => now()->subDay()]);
        $processedAt = $this->root->fresh()->processed_at;

        $this->actingAs(moderator($this))->postJson($this->url)
            ->assertOk()
            ->assertJsonCount(1, 'annotation.replies')
            ->assertJsonPath('annotation.replies.0.id', $reply->id);

        $fresh = $this->root->fresh();
        expect($fresh->highlighted_text)->toBe('premier passage')
            ->and($fresh->prefix)->toBe('avant ')
            ->and($fresh->suffix)->toBe(' après')
            ->and((bool) $fresh->is_processed)->toBeTrue()
            ->and($fresh->processed_at->equalTo($processedAt))->toBeTrue()
            ->and($reply->fresh()->trashed())->toBeFalse()
            ->and($reply->fresh()->body)->toBe('<p>Réponse</p>');
    });

    it('is idempotent', function () {
        $moderator = moderator($this);
        $this->actingAs($moderator)->postJson($this->url)->assertOk();
        $firstEmptiedAt = $this->root->fresh()->content_emptied_at;

        $this->travel(1)->hours();
        $this->actingAs($moderator)->postJson($this->url)
            ->assertOk()
            ->assertJsonPath('annotation.is_emptied', true);

        expect($this->root->fresh()->content_emptied_at->equalTo($firstEmptiedAt))->toBeTrue()
            ->and($this->root->fresh()->body)->toBe($this->placeholder);
    });

    it('refuses a reply with 422', function () {
        $reply = CommentAnnotation::query()->create([
            'comment_id' => $this->comment->id,
            'parent_annotation_id' => $this->root->id,
            'author_id' => $this->author->id,
            'body' => '<p>Réponse</p>',
        ]);

        $this->actingAs(moderator($this))
            ->postJson('/comments/annotations/' . $reply->id . '/empty-content')
            ->assertStatus(422);

        expect($reply->fresh()->body)->toBe('<p>Réponse</p>')
            ->and($reply->fresh()->content_emptied_at)->toBeNull();
    });

    it('returns 404 for an unknown or deleted annotation', function () {
        $moderator = moderator($this);
        $this->actingAs($moderator)
            ->postJson('/comments/annotations/' . ($this->root->id + 999) . '/empty-content')
            ->assertNotFound();

        $this->root->delete();
        $this->actingAs($moderator)->postJson($this->url)->assertNotFound();
    });

    it('turns away the commenter, the author and another confirmed reader, and leaves the row untouched', function () {
        $other = carol($this);
        foreach ([$this->reader, $this->author, $other] as $user) {
            // The role middleware redirects non-moderators to the dashboard (same as moderator delete).
            $this->actingAs($user)->post($this->url)->assertRedirect(route('dashboard'));
        }

        expect($this->root->fresh()->body)->toBe('<p>Premier avis</p>')
            ->and($this->root->fresh()->content_emptied_at)->toBeNull();
    });

    it('rejects a guest', function () {
        $this->postJson($this->url)->assertUnauthorized();
        $this->post($this->url)->assertRedirect(route('login'));

        expect($this->root->fresh()->content_emptied_at)->toBeNull();
    });

    it('emits no event and sends no notification', function () {
        $moderator = moderator($this);
        Notification::fake();
        $before = count(app(EventPublicApi::class)->list());

        $this->actingAs($moderator)->postJson($this->url)->assertOk();

        expect(count(app(EventPublicApi::class)->list()))->toBe($before);
        Notification::assertNothingSent();
    });

    it('shows the commenter an emptied, non-editable row and the author the placeholder', function () {
        $this->actingAs(moderator($this))->postJson($this->url)->assertOk();
        $listUrl = '/comments/' . $this->comment->id . '/annotations';

        $this->actingAs($this->reader)->getJson($listUrl)
            ->assertOk()
            ->assertJsonPath('items.0.is_emptied', true)
            ->assertJsonPath('items.0.can_edit', false)
            ->assertJsonPath('items.0.body', $this->placeholder);

        $this->actingAs($this->author)->getJson($listUrl)
            ->assertOk()
            ->assertJsonPath('items.0.body', $this->placeholder)
            ->assertJsonPath('items.0.is_emptied', true);
    });
});
