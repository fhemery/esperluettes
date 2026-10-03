<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Public\Api\CommentMaintenancePublicApi;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Annotation lifecycle', function () {
    beforeEach(function () {
        \Illuminate\Support\Facades\Cache::flush();

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
        $this->url = '/comments/' . $this->comment->id . '/annotations';

        $this->deactivate = function () {
            $this->actingAs(admin($this));
            app(AuthPublicApi::class)->deactivateUserById($this->reader->id);
            auth()->logout();
        };
        $this->reactivate = function () {
            $this->actingAs(admin($this));
            app(AuthPublicApi::class)->activateUserById($this->reader->id);
            auth()->logout();
        };
    });

    it('moderator delete of the root comment leaves no annotation row', function () {
        $this->actingAs(moderator($this))
            ->delete(route('comments.moderation.delete', ['commentId' => $this->comment->id]))
            ->assertRedirect();

        expect(CommentAnnotation::withTrashed()->count())->toBe(0);
    });

    it('moderator empty-content soft-deletes the comment\'s annotations, the comment stays', function () {
        $this->actingAs(moderator($this))
            ->post(route('comments.moderation.empty-content', ['commentId' => $this->comment->id]))
            ->assertRedirect();

        expect(Comment::query()->find($this->comment->id))->not->toBeNull()
            ->and(CommentAnnotation::query()->count())->toBe(0)
            ->and(CommentAnnotation::onlyTrashed()->count())->toBe(2);

        $this->actingAs($this->author)->getJson($this->url)
            ->assertOk()
            ->assertJsonCount(0, 'items');
    });

    it('user deleted: annotations kept with author_id null, still listed to the chapter author', function () {
        deleteUser($this, $this->reader);

        expect(CommentAnnotation::query()->count())->toBe(2)
            ->and(CommentAnnotation::query()->whereNotNull('author_id')->count())->toBe(0);

        $this->actingAs($this->author)->getJson($this->url)
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.author_id', null);
    });

    it('user deactivated: GET on their comment answers 404 and the comment list no longer shows it', function () {
        ($this->deactivate)();

        $this->actingAs($this->author)->getJson($this->url)->assertNotFound();
        expect(app(CommentPublicApi::class)->getFor('chapter', (int) $this->chapter->id, 1, 10)->total)->toBe(0);
    });

    it('user reactivated: annotations are listed again', function () {
        ($this->deactivate)();
        ($this->reactivate)();

        $this->actingAs($this->author)->getJson($this->url)
            ->assertOk()
            ->assertJsonCount(2, 'items');
    });

    it('an annotation deleted by a moderator stays deleted after its author is deactivated then reactivated', function () {
        $this->actingAs(moderator($this))
            ->deleteJson('/comments/annotations/' . $this->first->id)
            ->assertNoContent();

        ($this->deactivate)();
        ($this->reactivate)();

        $this->actingAs($this->author)->getJson($this->url)
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $this->second->id);
    });

    it('annotations of an emptied root comment stay deleted after deactivate/reactivate', function () {
        $this->actingAs(moderator($this))
            ->post(route('comments.moderation.empty-content', ['commentId' => $this->comment->id]))
            ->assertRedirect();

        ($this->deactivate)();
        ($this->reactivate)();

        $this->actingAs($this->author)->getJson($this->url)
            ->assertOk()
            ->assertJsonCount(0, 'items');
    });

    it('CommentMaintenancePublicApi::deleteFor removes the annotations', function () {
        app(CommentMaintenancePublicApi::class)->deleteFor('chapter', (int) $this->chapter->id);

        expect(CommentAnnotation::withTrashed()->count())->toBe(0);
    });
});
