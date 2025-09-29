<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Keep Writing Component', function () {

    it('should show an error if user is not authenticated', function() {
        $html = Blade::render('<x-story::keep-writing-component />');

        expect($html)
            ->toContain(__('story::keep-writing.title'))
            ->toContain(__('story::keep-writing.errors.not_authenticated'));
    });

    it('shows empty state when user has no story to continue', function () {
        $user = bob($this);
        $this->actingAs($user);

        // Ensure no authored stories with chapters
        // Render the component via Blade
        $html = Blade::render('<x-story::keep-writing-component />');

        expect($html)
            ->toContain(__('story::keep-writing.title'))
            ->toContain(__('story::keep-writing.empty'))
            ->toContain(__('story::keep-writing.new_story'))
            ->toContain(route('stories.create'));
    });

    it('shows latest authored story and action when available', function () {
        $author = alice($this);
        $this->actingAs($author);

        // Create a public story authored by the user with a recently edited chapter
        $story = publicStory('My Latest Story', $author->id);
        createPublishedChapter($this, $story, $author, ['title' => 'Chapter 1']);

        // Touch a new chapter to ensure it is the latest edited
        $latest = createPublishedChapter($this, $story, $author, ['title' => 'Chapter 2']);
        // Ensure last_edited_at is most recent
        Chapter::whereKey($latest->id)->update(['last_edited_at' => now()]);

        $html = Blade::render('<x-story::keep-writing-component />');

        expect($html)
            ->toContain(__('story::keep-writing.title'))
            ->toContain('My Latest Story')
            ->toContain(__('story::keep-writing.new_chapter'))
            ->toContain(route('chapters.create', ['storySlug' => $story->slug]));
    });

    it('should disable the chapter creation button if the user has no credits left', function () {
        $author = alice($this);
        $this->actingAs($author);

        // Create a public story authored by the user with a recently edited chapter
        $story = publicStory('My Latest Story', $author->id);
        createPublishedChapter($this, $story, $author, ['title' => 'Chapter 1']);

        // Set the user's credits to 0
        setUserCredits($author->id, 0);

        $html = Blade::render('<x-story::keep-writing-component />');

        expect($html)
            ->toContain('My Latest Story')
            ->toContain(__('story::keep-writing.new_chapter'))
            ->toContain('disabled="disabled"');
    });

    it('should show an error if the user is not confirmed', function () {
        $user = bob($this, roles: [Roles::USER]);
        $this->actingAs($user);

        setUserCredits($user->id, 1);

        $html = Blade::render('<x-story::keep-writing-component />');

        expect($html)
            ->toContain(__('story::keep-writing.title'))
            ->toContain(__('story::keep-writing.cannot_write'))
            ->toContain(__('story::keep-writing.go_to_stories'))
            ->toContain(route('stories.index'));
    });
});
