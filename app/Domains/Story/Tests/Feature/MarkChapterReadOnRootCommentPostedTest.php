<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Marking chapter read on commenting', function () {
    it('marks chapter as read when a root comment is posted on a chapter', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Story + published chapter
        $story = publicStory('Story A', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        // Sanity
        expect((int)($chapter->reads_logged_count ?? 0))->toBe(0);
        expect((int)($story->reads_logged_total ?? 0))->toBe(0);

        $this->actingAs($reader);
        createComment('chapter', (int)$chapter->id, generateDummyText(150), null);

        // Assert reading progress created for reader
        $exists = DB::table('story_reading_progress')
            ->where('user_id', $reader->id)
            ->where('story_id', $story->id)
            ->where('chapter_id', $chapter->id)
            ->exists();
        expect($exists)->toBeTrue();

        // Refresh and assert counters incremented
        $chapter = Chapter::query()->findOrFail($chapter->id);
        $story = Story::query()->findOrFail($story->id);
        expect((int)$chapter->reads_logged_count)->toBe(1);
        expect((int)$story->reads_logged_total)->toBe(1);
    });

    it('does nothing when a reply comment is posted on a chapter (no read logged for replier)', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $rootAuthor = carol($this, roles: [Roles::USER_CONFIRMED]);
        $replier = bob($this, roles: [Roles::USER_CONFIRMED]);

        $story = publicStory('Story B', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($rootAuthor);
        $rootId = createComment('chapter', (int)$chapter->id, generateDummyText(150), null);
       
        // Snapshot counts after Carol's root
        $chapter->refresh();
        $story->refresh();
        expect((int)$chapter->reads_logged_count)->toBe(1);
        expect((int)$story->reads_logged_total)->toBe(1);

        // Bob posts a reply to Carol's comment
        $this->actingAs($replier);
        createComment('chapter', (int)$chapter->id, '<p>Replying</p>', $rootId);

        // Assert no reading progress for Bob
        $exists = DB::table('story_reading_progress')
            ->where('user_id', $replier->id)
            ->where('story_id', $story->id)
            ->where('chapter_id', $chapter->id)
            ->exists();
        expect($exists)->toBeFalse();

        // Counters unchanged (still only Carol's read)
        $chapter = Chapter::query()->findOrFail($chapter->id);
        $story = Story::query()->findOrFail($story->id);
        expect((int)$chapter->reads_logged_count)->toBe(1);
        expect((int)$story->reads_logged_total)->toBe(1);
    });
});
