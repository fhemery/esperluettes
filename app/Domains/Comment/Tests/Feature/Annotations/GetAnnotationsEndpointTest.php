<?php

use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('GET /comments/{commentId}/annotations', function () {
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
                ['body' => '<p>Second avis</p>', 'highlighted_text' => 'second passage', 'prefix' => null, 'suffix' => null],
            ]),
        ])->assertSessionHasNoErrors();
        auth()->logout();

        $this->comment = Comment::query()->sole();
        $this->annotations = CommentAnnotation::query()->orderBy('id')->get();
        $this->url = '/comments/' . $this->comment->id . '/annotations';
    });

    it('the commenter gets their annotations without the processed flag and no actions', function () {
        $response = $this->actingAs($this->reader)->getJson($this->url);

        $response->assertOk();
        $response->assertJsonPath('comment_id', $this->comment->id);
        $response->assertJsonPath('viewer_role', 'commenter');
        $response->assertJsonCount(2, 'items');

        $first = $response->json('items.0');
        expect($first['id'])->toBe($this->annotations[0]->id)
            ->and($first['comment_id'])->toBe($this->comment->id)
            ->and($first['parent_annotation_id'])->toBeNull()
            ->and($first['author_id'])->toBe($this->reader->id)
            ->and($first['author_profile']['user_id'])->toBe($this->reader->id)
            ->and($first['body'])->toContain('Premier avis')
            ->and($first['highlighted_text'])->toBe('premier passage')
            ->and($first['prefix'])->toBe('avant ')
            ->and($first['suffix'])->toBe(' après')
            ->and($first['created_at'])->not->toBeEmpty()
            ->and($first['replies'])->toBe([])
            ->and($first)->toHaveKey('is_processed')
            ->and($first['is_processed'])->toBeNull()
            ->and($first['can_mark_as_processed'])->toBeFalse()
            ->and($first['can_delete'])->toBeFalse();
        expect($response->json('items.1.highlighted_text'))->toBe('second passage');
    });

    it('the chapter author gets them with is_processed and can_mark_as_processed', function () {
        $this->annotations[1]->update(['is_processed' => true]);

        $response = $this->actingAs($this->author)->getJson($this->url);

        $response->assertOk();
        $response->assertJsonPath('viewer_role', 'author');
        $response->assertJsonCount(2, 'items');
        $response->assertJsonPath('items.0.is_processed', false);
        $response->assertJsonPath('items.1.is_processed', true);
        $response->assertJsonPath('items.0.can_mark_as_processed', true);
        $response->assertJsonPath('items.0.can_delete', false);
    });

    it('a co-author gets the same as the author', function () {
        $coAuthor = carol($this);
        addCollaborator($this->story->id, $coAuthor->id, 'author');

        $response = $this->actingAs($coAuthor)->getJson($this->url);

        $response->assertOk();
        $response->assertJsonPath('viewer_role', 'author');
        $response->assertJsonCount(2, 'items');
        $response->assertJsonPath('items.0.is_processed', false);
        $response->assertJsonPath('items.0.can_mark_as_processed', true);
        $response->assertJsonPath('items.0.can_delete', false);
    });

    it('a beta reader gets 403', function () {
        $betaReader = carol($this);
        addCollaborator($this->story->id, $betaReader->id, 'beta-reader');

        $response = $this->actingAs($betaReader)->getJson($this->url);

        $response->assertForbidden();
        expect($response->getContent())->not->toContain('premier passage')
            ->and($response->getContent())->not->toContain('Premier avis');
    });

    it('another reader gets 403', function () {
        $other = daniel($this);

        $response = $this->actingAs($other)->getJson($this->url);

        $response->assertForbidden();
        expect($response->getContent())->not->toContain('premier passage')
            ->and($response->getContent())->not->toContain('Premier avis');
    });

    it('a moderator gets them with can_delete and without can_mark_as_processed', function () {
        $moderator = moderator($this);

        $response = $this->actingAs($moderator)->getJson($this->url);

        $response->assertOk();
        $response->assertJsonPath('viewer_role', 'moderator');
        $response->assertJsonCount(2, 'items');
        $response->assertJsonPath('items.0.is_processed', false);
        $response->assertJsonPath('items.0.can_mark_as_processed', false);
        $response->assertJsonPath('items.0.can_delete', true);
    });

    it('a guest is redirected to login', function () {
        $this->get($this->url)->assertRedirect(route('login'));
    });

    it('an unknown comment id answers 404', function () {
        $this->actingAs($this->reader)
            ->getJson('/comments/' . ($this->comment->id + 999) . '/annotations')
            ->assertNotFound();
    });

    it('soft-deleted annotations are not listed', function () {
        $this->annotations[0]->delete();

        $response = $this->actingAs($this->author)->getJson($this->url);

        $response->assertOk();
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.id', $this->annotations[1]->id);
    });

    it('highlighted_text is returned as stored (plain text)', function () {
        $raw = 'a < b & "c" <em>pas du html</em>';
        $this->annotations[0]->update(['highlighted_text' => $raw]);

        $response = $this->actingAs($this->author)->getJson($this->url);

        $response->assertOk();
        expect($response->json('items.0.highlighted_text'))->toBe($raw);
    });
});
