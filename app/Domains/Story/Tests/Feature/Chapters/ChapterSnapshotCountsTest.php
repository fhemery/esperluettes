<?php

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ChapterSnapshot counts', function () {

    it('reports the persisted word and character counts', function () {
        $user = alice($this);
        $this->actingAs($user);
        $story = createStoryForAuthor($user->id, ['title' => 'Counting Story']);

        $resp = $this->post(route('chapters.store', ['storySlug' => $story->slug]), validChapterPayload([
            'title' => 'Counted Chapter',
            'content' => '<p>Some words here for counting</p>',
        ]));
        $resp->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();

        $snapshot = ChapterSnapshot::fromModel($chapter);

        expect($chapter->word_count)->toBeGreaterThan(0);
        expect($snapshot->wordCount)->toBe((int) $chapter->word_count);
        expect($snapshot->charCount)->toBe((int) $chapter->character_count);
    });

    it('reports a char count matching the column for entity-heavy content', function () {
        $user = alice($this);
        $this->actingAs($user);
        $story = createStoryForAuthor($user->id, ['title' => 'Entity Story']);

        $resp = $this->post(route('chapters.store', ['storySlug' => $story->slug]), validChapterPayload([
            'title' => 'Entity Chapter',
            'content' => '<p>Rock &amp; Roll &amp; Blues &amp; Jazz</p>',
        ]));
        $resp->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();

        // The stored HTML really does carry entities, otherwise this test proves nothing.
        expect($chapter->content)->toContain('&amp;');

        $snapshot = ChapterSnapshot::fromModel($chapter);

        expect($snapshot->charCount)->toBe((int) $chapter->character_count);
        // and it differs from the old, entity-blind rule
        expect($snapshot->charCount)->toBeLessThan(mb_strlen(strip_tags($chapter->content)));
    });
});

describe('story_chapters.content_blocks', function () {

    it('exists as a nullable column and is null for existing chapters', function () {
        expect(Schema::hasColumn('story_chapters', 'content_blocks'))->toBeTrue();

        $user = alice($this);
        $this->actingAs($user);
        $story = createStoryForAuthor($user->id, ['title' => 'Blocks Story']);

        $this->post(route('chapters.store', ['storySlug' => $story->slug]), validChapterPayload([
            'title' => 'Simple Chapter',
            'content' => '<p>Body</p>',
        ]))->assertRedirect();

        /** @var Chapter $chapter */
        $chapter = Chapter::query()->latest('id')->firstOrFail();
        expect($chapter->content_blocks)->toBeNull();
    });

    it('casts content_blocks to an array', function () {
        $user = alice($this);
        $story = createStoryForAuthor($user->id, ['title' => 'Cast Story']);

        $chapter = Chapter::query()->create([
            'story_id' => $story->id,
            'title' => 'Cast Chapter',
            'slug' => 'cast-chapter',
            'content' => '<p>Body</p>',
            'sort_order' => 1,
            'status' => Chapter::STATUS_NOT_PUBLISHED,
            'content_blocks' => [['type' => 'text', 'html' => '<p>Body</p>']],
        ]);

        $fresh = Chapter::query()->findOrFail($chapter->id);
        expect($fresh->content_blocks)->toBe([['type' => 'text', 'html' => '<p>Body</p>']]);
    });
});
