<?php

use App\Domains\Story\Public\Moderation\StorySnapshotFormatter;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StorySnapshotFormatter', function () {
    it('capture returns expected snapshot structure and values', function () {
        $user = alice($this);
        /** @var Story $story */
        $story = publicStory('My <b>Story</b>', $user->id, [
            'description' => 'Short <i>summary</i>',
        ]);

        $formatter = new StorySnapshotFormatter();
        $snapshot = $formatter->capture($story->id);

        expect($snapshot)
            ->toHaveKeys(['title', 'summary'])
            ->and($snapshot['title'])->toBe('My <b>Story</b>')
            ->and($snapshot['summary'])->toBe('Short <i>summary</i>');
    });

    it('render outputs expected HTML using snapshot and escapes values', function () {
        $formatter = new StorySnapshotFormatter();
        $snapshot = [
            'title' => 'Title <script>alert(1)</script>',
            'summary' => 'Summary with <b>bold</b>',
        ];

        $html = $formatter->render($snapshot);

        expect($html)
            ->toContain(__('story::moderation.title'))
            ->toContain(__('story::moderation.summary'))
            ->not->toContain('<script>')
            ->toContain(e('Title <script>alert(1)</script>'))
            ->toContain(e('Summary with <b>bold</b>'));
    });

    it('getReportedUserId returns created_by_user_id', function () {
        $user = alice($this);
        /** @var Story $story */
        $story = publicStory('T', $user->id, [
            'description' => 'D',
        ]);

        $formatter = new StorySnapshotFormatter();
        expect($formatter->getReportedUserId($story->id))->toBe($user->id);
    });

    it('getContentUrl returns the story show route using slug', function () {
        $user = alice($this);
        /** @var Story $story */
        $story = publicStory('T', $user->id, [
            'description' => 'D',
        ]);

        $formatter = new StorySnapshotFormatter();
        $url = $formatter->getContentUrl($story->id);

        expect($url)->toBe(route('stories.show', ['slug' => $story->slug]));
    });
});
