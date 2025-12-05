<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
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

    it('selects the most recent between last edited chapter and last created story', function () {
        $author = alice($this);
        $this->actingAs($author);

        // Story A: has chapters, last edited in the past
        $storyA = publicStory('Story With Chapters', $author->id);
        $chapterA1 = createPublishedChapter($this, $storyA, $author, ['title' => 'C1']);
        // Make sure last_edited_at is older
        Chapter::whereKey($chapterA1->id)->update(['last_edited_at' => now()->subDays(2)]);

        // Story B: newer story without chapters, created more recently than the last chapter edit
        // Sleep a tiny moment to ensure different timestamp order if needed
        $storyB = publicStory('Brand New Idea', $author->id);
        Story::whereKey($storyB->id)->update(['created_at' => now()->subDay()]);

        // Now update Story A with a newer chapter edit to surpass storyB created_at
        $chapterA2 = createPublishedChapter($this, $storyA, $author, ['title' => 'C2']);
        Chapter::whereKey($chapterA2->id)->update(['last_edited_at' => now()]);

        $html = Blade::render('<x-story::keep-writing-component />');

        // Should pick Story A because its latest chapter edit is now the most recent activity
        expect($html)
            ->toContain('Story With Chapters')
            ->toContain(__('story::keep-writing.new_chapter'))
            ->toContain(route('chapters.create', ['storySlug' => $storyA->slug]));
    });

    it('shows the last created story when there are no chapters at all', function () {
        $author = alice($this);
        $this->actingAs($author);

        // Two stories, no chapters on either
        $older = publicStory('Older Draft', $author->id);
        Story::whereKey($older->id)->update(['created_at' => now()->subDays(3)]);
        $newer = publicStory('New Shiny Draft', $author->id);
        Story::whereKey($newer->id)->update(['created_at' => now()->subDay()]);

        $html = Blade::render('<x-story::keep-writing-component />');

        expect($html)
            ->toContain('New Shiny Draft')
            ->toContain(__('story::keep-writing.new_chapter'))
            ->toContain(route('chapters.create', ['storySlug' => $newer->slug]));
    });

    it('shows empty state when the only authored story is completed', function () {
        $author = alice($this);
        $this->actingAs($author);

        $story = publicStory('Completed Draft', $author->id);
        $story->is_complete = true;
        $story->saveQuietly();

        $html = Blade::render('<x-story::keep-writing-component />');

        expect($html)
            ->toContain(__('story::keep-writing.title'))
            ->toContain(__('story::keep-writing.empty'))
            ->toContain(__('story::keep-writing.new_story'))
            ->toContain(route('stories.create'))
            ->not->toContain('Completed Draft');
    });

    it('skips completed latest story and shows the next incomplete one', function () {
        $author = alice($this);
        $this->actingAs($author);

        // Older, incomplete story with activity in the past
        $incomplete = publicStory('Ongoing Draft', $author->id);
        $chapterOld = createPublishedChapter($this, $incomplete, $author, ['title' => 'Old']);
        Chapter::whereKey($chapterOld->id)->update(['last_edited_at' => now()->subDays(3)]);

        // More recent story marked as complete
        $complete = publicStory('Finished Saga', $author->id);
        $chapterNew = createPublishedChapter($this, $complete, $author, ['title' => 'Final']);
        Chapter::whereKey($chapterNew->id)->update(['last_edited_at' => now()->subDay()]);
        $complete->is_complete = true;
        $complete->saveQuietly();

        $html = Blade::render('<x-story::keep-writing-component />');

        expect($html)
            ->toContain(__('story::keep-writing.title'))
            ->toContain('Ongoing Draft')
            ->toContain(__('story::keep-writing.new_chapter'))
            ->toContain(route('chapters.create', ['storySlug' => $incomplete->slug]))
            ->not->toContain('Finished Saga');
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
