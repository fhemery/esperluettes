<?php

declare(strict_types=1);

use App\Domains\ReadList\Private\Models\ReadListEntry;
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

                if ($i % 3 == 0) {
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

        it('shows all stories when hide_up_to_date parameter is explicitly set to 0', function () {
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

            // Test with explicit hide_up_to_date=0 parameter - should show both stories
            $this->get(route('readlist.index', ['hide_up_to_date' => '0']))
                ->assertOk()
                ->assertSee('Story with unread chapters')
                ->assertSee('Fully read story');
        });

        it('shows all stories when user unchecks hide_up_to_date checkbox', function () {
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

            // Set user's preference to hide up-to-date stories (simulating they checked it before)
            setSettingsValue($reader->id, 'readlist', 'hide-up-to-date', true);

            // Now simulate unchecking the checkbox - this would submit hide_up_to_date=0
            $this->get(route('readlist.index', ['hide_up_to_date' => '0']))
                ->assertOk()
                ->assertSee('Story with unread chapters')
                ->assertSee('Fully read story'); // This now works correctly with the fix
        });
    });

    describe('genre filter', function () {
        it('shows the genre select next to the up-to-date checkbox', function () {
            $user = alice($this);
            $this->actingAs($user);

            $response = $this->get(route('readlist.index'));
            $response
                ->assertOk()
                ->assertSee('readlist::page.filters.genre.placeholder');
        });

        it('displays available genres inside the dropdown', function () {
            $user = alice($this);
            $this->actingAs($user);
            makeRefGenre('Fantasy');

            $response = $this->get(route('readlist.index'));

            $response
                ->assertOk()
                // Seeded StoryRef data contains a "Fantasy" genre
                ->assertSee('Fantasy');
        });

        it('filters stories by the selected genre', function () {
            $author = alice($this);
            $reader = bob($this);

            // Create two genres
            $fantasy = makeRefGenre('Fantasy');
            $romance = makeRefGenre('Romance');

            setUserCredits($author->id, 10);

            // Story with Fantasy genre
            $this->actingAs($author);
            $fantasyStory = publicStory('Fantasy Story', $author->id, [
                'story_ref_genre_ids' => [$fantasy->id],
            ]);
            createPublishedChapter($this, $fantasyStory, $author);

            // Story with Romance genre
            $romanceStory = publicStory('Romance Story', $author->id, [
                'story_ref_genre_ids' => [$romance->id],
            ]);
            createPublishedChapter($this, $romanceStory, $author);

            // Add both stories to reader's readlist
            $this->actingAs($reader);
            addToReadList($this, $fantasyStory->id);
            addToReadList($this, $romanceStory->id);

            // Without genre filter, both stories should be visible
            $this->get(route('readlist.index'))
                ->assertOk()
                ->assertSee('Fantasy Story')
                ->assertSee('Romance Story');

            // With genre filter set to Fantasy, only Fantasy Story should be visible
            $this->get(route('readlist.index', ['genre_id' => $fantasy->id]))
                ->assertOk()
                ->assertSee('Fantasy Story')
                ->assertDontSee('Romance Story');
        });
    });

    describe('Complete stories', function () {
        it('shows completed badge only for completed stories in readlist', function () {
            $user = alice($this);
            $this->actingAs($user);

            $complete = publicStory('Complete On Readlist', $user->id);
            $complete->is_complete = true;
            $complete->saveQuietly();

            $ongoing = publicStory('Ongoing On Readlist', $user->id);
            $ongoing->is_complete = false;
            $ongoing->saveQuietly();

            ReadListEntry::create(['user_id' => $user->id, 'story_id' => $complete->id]);
            ReadListEntry::create(['user_id' => $user->id, 'story_id' => $ongoing->id]);

            $resp = $this->get(route('readlist.index'));
            $resp->assertOk();

            $resp->assertSee('Complete On Readlist');
            $resp->assertSee('Ongoing On Readlist');

            // Badge icon appears for completed story
            $resp->assertSee('done_all');
            // Tooltip text from story show translations should be present
            $resp->assertSee(trans('story::show.is_complete.tooltip'));
        });
    });
});
