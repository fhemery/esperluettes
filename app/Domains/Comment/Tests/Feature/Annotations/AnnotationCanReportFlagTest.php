<?php

use App\Domains\Comment\Private\Models\CommentAnnotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('can_report display hint on annotation DTOs', function () {
    beforeEach(function () {
        Cache::flush();

        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
        $this->reader = bob($this);

        $this->actingAs($this->reader);
        $this->commentId = createComment('chapter', $this->chapter->id, generateDummyText(140));
        auth()->logout();

        $this->root = CommentAnnotation::query()->create([
            'comment_id' => $this->commentId,
            'author_id' => $this->reader->id,
            'body' => '<p>Avis</p>',
            'highlighted_text' => 'passage',
        ]);
        $this->makeReply = fn (int $writerId) => CommentAnnotation::query()->forceCreate([
            'comment_id' => $this->commentId,
            'parent_annotation_id' => $this->root->id,
            'author_id' => $writerId,
            'body' => '<p>Réponse</p>',
            'highlighted_text' => '',
        ]);
        $this->url = '/comments/' . $this->commentId . '/annotations';
    });

    it('flags reader annotations reportable for an author, not their own replies', function () {
        ($this->makeReply)($this->author->id);
        ($this->makeReply)($this->reader->id);

        $item = $this->actingAs($this->author)->getJson($this->url)->assertOk()->json('items.0');

        expect($item['can_report'])->toBeTrue()
            ->and($item['replies'])->toHaveCount(2);
        $byAuthor = collect($item['replies'])->keyBy('author_id');
        expect($byAuthor[$this->author->id]['can_report'])->toBeFalse()
            ->and($byAuthor[$this->reader->id]['can_report'])->toBeTrue();
    });

    it('flags author replies reportable for the commenter, not their own roots', function () {
        ($this->makeReply)($this->author->id);

        $item = $this->actingAs($this->reader)->getJson($this->url)->assertOk()->json('items.0');

        expect($item['can_report'])->toBeFalse()
            ->and($item['replies'][0]['can_report'])->toBeTrue();
    });

    it('flags everything reportable for a moderator except emptied roots', function () {
        ($this->makeReply)($this->author->id);

        $item = $this->actingAs(moderator($this))->getJson($this->url)->assertOk()->json('items.0');
        expect($item['can_report'])->toBeTrue()
            ->and($item['replies'][0]['can_report'])->toBeTrue();

        $this->root->update(['content_emptied_at' => now()]);
        $item = $this->actingAs(moderator($this))->getJson($this->url)->assertOk()->json('items.0');
        expect($item['can_report'])->toBeFalse()
            ->and($item['replies'][0]['can_report'])->toBeTrue();
    });

    it('does not flag an emptied root annotation', function () {
        $this->root->update(['content_emptied_at' => now()]);

        $item = $this->actingAs($this->author)->getJson($this->url)->assertOk()->json('items.0');

        expect($item['is_emptied'])->toBeTrue()
            ->and($item['can_report'])->toBeFalse();
    });

    it('flags a reaction like any annotation', function () {
        $this->root->update(['body' => '<p>👍</p>']);

        $item = $this->actingAs($this->author)->getJson($this->url)->assertOk()->json('items.0');

        expect($item['can_report'])->toBeTrue();
    });

    it('carries can_report on the in-entity payload', function () {
        ($this->makeReply)($this->author->id);
        $url = '/comments/annotations/in-entity?entity_type=chapter&entity_id=' . $this->chapter->id;

        $item = $this->actingAs($this->author)->getJson($url)->assertOk()->json('lists.0.items.0');
        expect($item['can_report'])->toBeTrue()
            ->and($item['replies'][0]['can_report'])->toBeFalse();

        $item = $this->actingAs($this->reader)->getJson($url)->assertOk()->json('lists.0.items.0');
        expect($item['can_report'])->toBeFalse()
            ->and($item['replies'][0]['can_report'])->toBeTrue();
    });
});
