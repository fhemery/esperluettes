<?php

use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('DELETE /comments/annotations/{annotationId}', function () {
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
        [$this->first, $this->second] = CommentAnnotation::query()->orderBy('id')->get()->all();
        $this->url = '/comments/annotations/' . $this->first->id;
    });

    it('a moderator soft-deletes one annotation; the others under the comment stay', function () {
        $reply = CommentAnnotation::query()->create([
            'comment_id' => $this->comment->id,
            'parent_annotation_id' => $this->first->id,
            'author_id' => $this->author->id,
            'body' => '<p>Réponse</p>',
            'highlighted_text' => 'premier passage',
        ]);

        $this->actingAs(moderator($this))->deleteJson($this->url)->assertNoContent();

        expect(CommentAnnotation::withTrashed()->find($this->first->id)->trashed())->toBeTrue()
            ->and(CommentAnnotation::withTrashed()->find($reply->id)->trashed())->toBeTrue()
            ->and($this->second->fresh()->trashed())->toBeFalse()
            ->and(Comment::query()->find($this->comment->id))->not->toBeNull();
    });

    it("the deleted annotation disappears from every viewer's GET payload", function () {
        $this->actingAs(admin($this))->deleteJson($this->url)->assertNoContent();

        $commentUrl = '/comments/' . $this->comment->id . '/annotations';
        foreach ([$this->reader, $this->author, moderator($this)] as $viewer) {
            $this->actingAs($viewer)->getJson($commentUrl)
                ->assertOk()
                ->assertJsonCount(1, 'items')
                ->assertJsonPath('items.0.id', $this->second->id);
        }
    });

    it('a non-moderator (author, commenter) is redirected by the role middleware and the row is untouched', function () {
        foreach ([$this->author, $this->reader] as $user) {
            $this->actingAs($user)->delete($this->url)->assertRedirect(route('dashboard'));
        }

        expect($this->first->fresh()->trashed())->toBeFalse();
    });

    it('an unknown id answers 404', function () {
        $this->actingAs(moderator($this))
            ->deleteJson('/comments/annotations/' . ($this->second->id + 999))
            ->assertNotFound();
    });
});
