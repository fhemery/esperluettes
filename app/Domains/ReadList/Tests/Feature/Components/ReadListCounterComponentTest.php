<?php

use App\Domains\ReadList\Private\Models\ReadListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReadListCounterComponent', function () {
    it('shows zero when no readers have added story', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $html = Blade::render('<x-read-list::read-list-counter-component :story-id="$storyId" />', [
            'storyId' => $story->id,
        ]);

        expect($html)
            ->toContain('0')
            ->toContain('bookmark');
    });

    it('shows count when readers have added story', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        // Add 3 readers
        $reader1 = bob($this);
        $reader2 = carol($this);

        $this->actingAs($reader1);
        addToReadList($this, $story->id);

        $this->actingAs($reader2);
        addToReadList($this, $story->id);

        $html = Blade::render('<x-read-list::read-list-counter-component :story-id="$storyId" />', [
            'storyId' => $story->id,
        ]);

        expect($html)
            ->toContain('2')
            ->toContain('bookmark');
    });

    it('includes tooltip text', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $reader = bob($this);
        ReadListEntry::create(['user_id' => $reader->id, 'story_id' => $story->id]);

        $html = Blade::render('<x-read-list::read-list-counter-component :story-id="$storyId" />', [
            'storyId' => $story->id,
        ]);

        expect($html)
            ->toContain(__('readlist::counter.counter_tooltip', ['count' => 1]));
    });

    it('works for any authentication state', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $reader = bob($this);
        ReadListEntry::create(['user_id' => $reader->id, 'story_id' => $story->id]);

        // Guest
        $html = Blade::render('<x-read-list::read-list-counter-component :story-id="$storyId" />', [
            'storyId' => $story->id,
        ]);

        expect($html)->toContain('bookmark');

        // Authenticated
        $this->actingAs($reader);
        $html = Blade::render('<x-read-list::read-list-counter-component :story-id="$storyId" />', [
            'storyId' => $story->id,
        ]);

        expect($html)->toContain('bookmark');
    });
});
