<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\ReadingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('KeepReadingComponent', function () {
    it('renders empty state when user has no progress', function () {
        $author = alice($this);
        $story = publicStory('Empty Progress Story', $author->id);
        createPublishedChapter($this, $story, $author, ['title' => 'C1']);

        $reader = bob($this);
        $this->actingAs($reader);

        $html = Blade::render('<x-story::keep-reading-component />');
        expect($html)
            ->toContain(__('story::keep-reading.title'))
            ->toContain(__('story::keep-reading.errors.not_authenticated') === __('story::keep-reading.errors.not_authenticated') ? '' : '') // noop to load lang
            ->toContain(__('story::keep-reading.empty'));
        expect($html)->not->toContain('Empty Progress Story');
    });

    it('shows story card when progress exists on a public story', function () {
        $author = alice($this);
        $story = publicStory('Reading Story', $author->id);
        $c1 = createPublishedChapter($this, $story, $author, ['title' => 'C1']);
        $c2 = createPublishedChapter($this, $story, $author, ['title' => 'C2']);

        $reader = bob($this);
        $this->actingAs($reader);

        // Mark first chapter as read -> component should propose next
        markAsRead($this, $c1)->assertNoContent();

        $html = Blade::render('<x-story::keep-reading-component />');
        expect($html)
            ->toContain('Reading Story')
            ->toContain('/stories/' . $story->slug)
            ->toContain($c2->slug);
    });

    it('does not show private story to non-collaborator even with progress row', function () {
        $author = alice($this);
        $story = privateStory('Private Hidden', $author->id);
        $c1 = createPublishedChapter($this, $story, $author, ['title' => 'C1']);

        $reader = bob($this); // not a collaborator
        $this->actingAs($reader);

        // Insert progress directly (legacy/seeded row)
        ReadingProgress::query()->create([
            'user_id' => $reader->id,
            'story_id' => $story->id,
            'chapter_id' => $c1->id,
            'read_at' => now(),
        ]);

        $html = Blade::render('<x-story::keep-reading-component />');
        expect($html)->not->toContain('Private Hidden');
    });

    it('shows community story only for confirmed users', function () {
        $author = alice($this);
        $story = communityStory('Community Read', $author->id);
        $c1 = createPublishedChapter($this, $story, $author, ['title' => 'C1']);
        createPublishedChapter($this, $story, $author, ['title' => 'C2']);

        // Non-confirmed cannot progress => component should not show
        $nonConfirmed = bob($this, roles: [Roles::USER]);
        $this->actingAs($nonConfirmed);
        markAsRead($this, $c1)->assertRedirect('/dashboard');
        $html = Blade::render('<x-story::keep-reading-component />');
        expect($html)->not->toContain('Community Read');

        // Confirmed can progress => component should show
        $confirmed = carol($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($confirmed);
        markAsRead($this, $c1)->assertNoContent();
        $html = Blade::render('<x-story::keep-reading-component />');
        expect($html)->toContain('Community Read');
    });
});
