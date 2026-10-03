<?php

use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Api\Contracts\CommentDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Post a root comment on the chapter as $user, with $nbAnnotations root annotations under it.
 */
function commentWithAnnotations(TestCase $t, $user, int $chapterId, int $nbAnnotations): int
{
    $t->actingAs($user);
    $commentId = createComment('chapter', $chapterId, generateDummyText(140));
    for ($i = 0; $i < $nbAnnotations; $i++) {
        CommentAnnotation::query()->create([
            'comment_id' => $commentId,
            'author_id' => $user->id,
            'body' => '<p>Avis ' . $i . '</p>',
            'highlighted_text' => 'passage ' . $i,
        ]);
    }
    auth()->logout();

    return $commentId;
}

/**
 * @return array<int,int> [commentId => annotationCount] as seen by $viewer
 */
function annotationCountsSeenBy(TestCase $t, $viewer, int $chapterId): array
{
    $t->actingAs($viewer);
    $list = app(CommentPublicApi::class)->getFor('chapter', $chapterId, 1, 20);

    $counts = [];
    foreach ($list->items as $item) {
        /** @var CommentDto $item */
        $counts[$item->id] = $item->annotationCount;
    }

    return $counts;
}

describe('annotationCount on CommentDto', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
        $this->bob = bob($this);
        $this->carol = carol($this);

        $this->bobComment = commentWithAnnotations($this, $this->bob, $this->chapter->id, 2);
        $this->carolComment = commentWithAnnotations($this, $this->carol, $this->chapter->id, 1);
    });

    it('the commenter sees the count on their own comment and 0 on others\'', function () {
        expect(annotationCountsSeenBy($this, $this->bob, $this->chapter->id))->toBe([
            $this->carolComment => 0,
            $this->bobComment => 2,
        ]);
    });

    it('the chapter author and a moderator see every comment\'s count', function () {
        $expected = [$this->carolComment => 1, $this->bobComment => 2];

        expect(annotationCountsSeenBy($this, $this->author, $this->chapter->id))->toBe($expected);
        expect(annotationCountsSeenBy($this, moderator($this), $this->chapter->id))->toBe($expected);
    });

    it('another reader sees 0 everywhere', function () {
        expect(annotationCountsSeenBy($this, daniel($this), $this->chapter->id))->toBe([
            $this->carolComment => 0,
            $this->bobComment => 0,
        ]);
    });

    it('soft-deleted annotations are not counted', function () {
        CommentAnnotation::query()->where('comment_id', $this->bobComment)->orderBy('id')->first()->delete();

        expect(annotationCountsSeenBy($this, $this->author, $this->chapter->id)[$this->bobComment])->toBe(1);
    });

    it('replies to a root comment carry 0', function () {
        $this->actingAs($this->author);
        createComment('chapter', $this->chapter->id, generateDummyText(140), $this->bobComment);

        $list = app(CommentPublicApi::class)->getFor('chapter', $this->chapter->id, 1, 20);
        $bob = collect($list->items)->firstWhere('id', $this->bobComment);

        expect($bob->annotationCount)->toBe(2)
            ->and($bob->children)->toHaveCount(1)
            ->and($bob->children[0]->annotationCount)->toBe(0);
    });

    it('the count query runs once per page whatever the number of comments', function () {
        commentWithAnnotations($this, daniel($this), $this->chapter->id, 3);
        commentWithAnnotations($this, moderator($this), $this->chapter->id, 0);

        $this->actingAs($this->author);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $list = app(CommentPublicApi::class)->getFor('chapter', $this->chapter->id, 1, 20);
        $annotationQueries = collect(DB::getQueryLog())
            ->filter(fn (array $q) => str_contains($q['query'], 'comment_annotations'));
        DB::disableQueryLog();

        expect($list->items)->toHaveCount(4)
            ->and($annotationQueries)->toHaveCount(1);
    });

    it('getComment carries the count for a root comment', function () {
        $this->actingAs($this->author);

        expect(app(CommentPublicApi::class)->getComment($this->bobComment)->annotationCount)->toBe(2);
    });
});
