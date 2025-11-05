<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\ReadList\Private\Models\ReadListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReadListToggleComponent', function () {
    it('renders nothing for guests', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        // Not authenticated
        $html = Blade::render('<x-read-list::read-list-toggle-component :story-id="$storyId" :is-author="false" />', [
            'storyId' => $story->id,
        ]);
        
        expect($html)->toBe('');
    });

    it('renders nothing when user is an author of the story', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);
        $this->actingAs($author);

        $html = Blade::render('<x-read-list::read-list-toggle-component :story-id="$storyId" :is-author="true" />', [
            'storyId' => $story->id,
        ]);
        
        expect($html)->toBe('');
    });

    it('renders add button when confirmed user does not have story in read list', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($reader);

        $html = Blade::render('<x-read-list::read-list-toggle-component :story-id="$storyId" :is-author="false" />', [
            'storyId' => $story->id,
        ]);
        
        expect($html)
            ->toContain(__('readlist::button.add_button'))
            ->toContain('form')
            ->toContain('method="POST"')
            ->toContain('/readlist/' . $story->id);
    });

    it('renders in-readlist button when confirmed user has story in read list', function () {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($reader);

        // Add story to read list
        ReadListEntry::create([
            'user_id' => $reader->id,
            'story_id' => $story->id,
        ]);

        $html = Blade::render('<x-read-list::read-list-toggle-component :story-id="$storyId" :is-author="false" />', [
            'storyId' => $story->id,
        ]);
        
        expect($html)
            ->toContain(__('readlist::button.in_readlist_button'))
            ->toContain('check')  // Material icon
            ->toContain('form')
            ->toContain('method="POST"')  // Laravel form spoofing
            ->toContain('/readlist/' . $story->id);
    });

    it('works for USER and USER_CONFIRMED role', function ($role) {
        $author = alice($this);
        $story = publicStory('Test Story', $author->id);

        $confirmedUser = bob($this, roles: [$role]);
        $this->actingAs($confirmedUser);

        $html = Blade::render('<x-read-list::read-list-toggle-component :story-id="$storyId" :is-author="false" />', [
            'storyId' => $story->id,
        ]);
        
        expect($html)
            ->toContain(__('readlist::button.add_button'));
    })->with([Roles::USER, Roles::USER_CONFIRMED]);

});
