<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReadList page', function () {
    it('requires authentication', function () {
        $this->get(route('readlist.index'))
            ->assertRedirect(route('login'));
    });

    it('renders the ReadList title for authenticated users', function () {
        $user = alice($this);
        $this->actingAs($user);

        $this->get(route('readlist.index'))
            ->assertOk()
            ->assertSee(__('readlist::page.title'));
    });

    describe('up-to-date filter', function () {
        it('shows the hide up-to-date filter checkbox', function () {
            $user = alice($this);
            $this->actingAs($user);

            $this->get(route('readlist.index'))
                ->assertOk()
                ->assertSee(__('readlist::page.filters.hide_up_to_date.label'))
                ->assertSee('name="hide_up_to_date"', false);
        });

        it('hides up-to-date stories when filter is checked', function () {
            // Create a user with stories in readlist
            $author = alice($this);

            // Create two stories: one with unread chapters, one fully read
            $storyWithUnread = publicStory('Story with unread chapters', $author->id);
            createPublishedChapter($this, $storyWithUnread, $author);
                
            $fullyReadStory = publicStory('Fully read story', $author->id);
            $readChapter = createPublishedChapter($this, $fullyReadStory, $author);

            $reader = bob($this);
            $this->actingAs($reader);
            markAsRead($this, $readChapter);
            addToReadList($this, $storyWithUnread->id);
            addToReadList($this, $fullyReadStory->id);

            // Test without filter - should show both stories
            $this->get(route('readlist.index'))
                ->assertOk()
                ->assertSee('Story with unread chapters')
                ->assertSee('Fully read story');

            // Test with filter checked - should only show story with unread chapters
            $this->get(route('readlist.index', ['hide_up_to_date' => '1']))
                ->assertOk()
                ->assertSee('Story with unread chapters')
                ->assertDontSee('Fully read story');
        });

        it('preserves hide up-to-date filter in loadMore endpoint', function () {
            $user = alice($this);
            $this->actingAs($user);

            // Create stories and add to readlist
            setUserCredits($user->id, 30);
            for ($i = 1; $i <= 25; $i++) {
                $storyWithUnread = publicStory('Story with unread chapters', $user->id);
                $readChapter = createPublishedChapter($this, $storyWithUnread, $user);
 
                $reader = bob($this);
                $this->actingAs($reader);
                addToReadList($this, $storyWithUnread->id);

                if ($i % 3 == 0){
                    markAsRead($this, $readChapter);
                }
            }

            // Test loadMore with hide_up_to_date filter
            $response = $this->get(route('readlist.load-more', [
                'page' => 2,
                'hide_up_to_date' => '1'
            ]));

            $response->assertOk();
            $data = $response->json();

            // Should have HTML content (stories with unread chapters only)
            // 25 stories, 8 read -> 17 unread -> 7 stories on second page
            $html = $data['html'];
            // Count number of time a story appears
            $count = substr_count($html, 'x-data="readListCard');
            $this->assertEquals(7, $count);

            $this->assertFalse($data['hasMore']);
            $this->assertEquals(3, $data['nextPage']);
        });
    });
});
