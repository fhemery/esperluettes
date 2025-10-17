<?php

use App\Domains\Story\Private\Support\Moderation\ChapterSnapshotFormatter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ChapterSnapshotFormatter', function () {
    it('capture returns expected snapshot structure and values', function () {
        $user = alice($this);
        /** @var Story $story */
        $story = publicStory('S', $user->id, ['description' => 'desc']);
        /** @var Chapter $chapter */
        $chapter = Chapter::query()->create([
            'story_id' => $story->id,
            'title' => 'Chap <b>One</b>',
            'slug' => 'chap-one-' . ($story->id + 1),
            'content' => str_repeat('A', 600),
            'status' => Chapter::STATUS_PUBLISHED,
            'sort_order' => 1,
        ]);

        $formatter = new ChapterSnapshotFormatter();
        $snapshot = $formatter->capture($chapter->id);

        expect($snapshot)
            ->toHaveKeys(['title', 'content'])
            ->and($snapshot['title'])->toBe('Chap <b>One</b>')
            ->and($snapshot['content'])->toBe(str_repeat('A', 600));
    });

    it('render outputs labels and see-more structure', function () {
        $formatter = new ChapterSnapshotFormatter();
        $snapshot = [
            'title' => 'T <script>x</script>',
            'content' => str_repeat('B', 500) . 'TAIL',
        ];

        $html = $formatter->render($snapshot);

        expect($html)
            ->toContain(__('story::moderation.chapter_title'))
            ->toContain(__('story::moderation.chapter_content'))
            ->toContain(__('story::moderation.see_more'))
            ->toContain(__('story::moderation.see_less'))
            ->not->toContain('<script>')
            ->toContain(e('T <script>x</script>'))
            // contains head and tail which implies full content present in DOM
            ->toContain(substr($snapshot['content'], 0, 50))
            ->toContain('TAIL');
    });

    it('getReportedUserId returns story.created_by_user_id', function () {
        $user = alice($this);
        $story = publicStory('S', $user->id, ['description' => '']);
        $chapter = Chapter::query()->create([
            'story_id' => $story->id,
            'title' => 'T',
            'slug' => 't-' . ($story->id + 2),
            'content' => 'C',
            'status' => Chapter::STATUS_PUBLISHED,
            'sort_order' => 1,
        ]);

        $formatter = new ChapterSnapshotFormatter();
        expect($formatter->getReportedUserId($chapter->id))->toBe($user->id);
    });

    it('getContentUrl returns the chapter show route using slugs', function () {
        $user = alice($this);
        $story = publicStory('S', $user->id, ['description' => '']);
        $chapter = Chapter::query()->create([
            'story_id' => $story->id,
            'title' => 'T',
            'slug' => 't-' . ($story->id + 3),
            'content' => 'C',
            'status' => Chapter::STATUS_PUBLISHED,
            'sort_order' => 1,
        ]);

        $formatter = new ChapterSnapshotFormatter();
        $url = $formatter->getContentUrl($chapter->id);

        expect($url)->toBe(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    });
});
