<?php

use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('PUT /comments/annotations/{annotationId}/processed', function () {
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
                ['body' => '<p>Premier avis</p>', 'highlighted_text' => 'premier passage', 'prefix' => null, 'suffix' => null],
                ['body' => '<p>Second avis</p>', 'highlighted_text' => 'second passage', 'prefix' => null, 'suffix' => null],
            ]),
        ])->assertSessionHasNoErrors();
        auth()->logout();

        $this->comment = Comment::query()->sole();
        $this->annotation = CommentAnnotation::query()->orderBy('id')->first();
        $this->url = '/comments/annotations/' . $this->annotation->id . '/processed';
    });

    it('the chapter author marks and unmarks an annotation (is_processed, processed_at)', function () {
        $this->actingAs($this->author)->putJson($this->url, ['value' => true])
            ->assertOk()
            ->assertExactJson(['id' => $this->annotation->id, 'is_processed' => true]);

        $row = $this->annotation->fresh();
        expect($row->is_processed)->toBeTrue()
            ->and($row->processed_at)->not->toBeNull();

        $this->actingAs($this->author)->putJson($this->url, ['value' => false])
            ->assertOk()
            ->assertExactJson(['id' => $this->annotation->id, 'is_processed' => false]);

        $row = $this->annotation->fresh();
        expect($row->is_processed)->toBeFalse()
            ->and($row->processed_at)->toBeNull();
    });

    it("a co-author's mark is seen by the other author", function () {
        $coAuthor = carol($this);
        addCollaborator($this->story->id, $coAuthor->id, 'author');

        $this->actingAs($coAuthor)->putJson($this->url, ['value' => true])->assertOk();

        $this->actingAs($this->author)
            ->getJson('/comments/' . $this->comment->id . '/annotations')
            ->assertOk()
            ->assertJsonPath('items.0.is_processed', true)
            ->assertJsonPath('items.1.is_processed', false);
    });

    it('the commenter gets 403 and the row is unchanged', function () {
        $this->actingAs($this->reader)->putJson($this->url, ['value' => true])->assertForbidden();

        $row = $this->annotation->fresh();
        expect($row->is_processed)->toBeFalse()
            ->and($row->processed_at)->toBeNull();
    });

    it('a beta reader, another reader and a moderator who is not an author get 403', function () {
        $betaReader = carol($this);
        addCollaborator($this->story->id, $betaReader->id, 'beta-reader');

        foreach ([$betaReader, daniel($this), moderator($this)] as $user) {
            $this->actingAs($user)->putJson($this->url, ['value' => true])->assertForbidden();
        }

        $row = $this->annotation->fresh();
        expect($row->is_processed)->toBeFalse()
            ->and($row->processed_at)->toBeNull();
    });

    it("the commenter's GET payload still has is_processed null after marking", function () {
        $this->actingAs($this->author)->putJson($this->url, ['value' => true])->assertOk();

        $this->actingAs($this->reader)
            ->getJson('/comments/' . $this->comment->id . '/annotations')
            ->assertOk()
            ->assertJsonPath('items.0.is_processed', null);
    });

    it('value is required and boolean (422)', function () {
        $this->actingAs($this->author)->putJson($this->url, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');
        $this->actingAs($this->author)->putJson($this->url, ['value' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');

        expect($this->annotation->fresh()->is_processed)->toBeFalse();
    });

    it('a reply row is refused (422)', function () {
        $reply = CommentAnnotation::query()->create([
            'comment_id' => $this->comment->id,
            'parent_annotation_id' => $this->annotation->id,
            'author_id' => $this->author->id,
            'body' => '<p>Réponse</p>',
            'highlighted_text' => 'premier passage',
        ]);

        $this->actingAs($this->author)
            ->putJson('/comments/annotations/' . $reply->id . '/processed', ['value' => true])
            ->assertUnprocessable();

        expect($reply->fresh()->is_processed)->toBeFalse();
    });

    it('an unknown id answers 404', function () {
        $this->actingAs($this->author)
            ->putJson('/comments/annotations/' . ($this->annotation->id + 999) . '/processed', ['value' => true])
            ->assertNotFound();
    });
});
