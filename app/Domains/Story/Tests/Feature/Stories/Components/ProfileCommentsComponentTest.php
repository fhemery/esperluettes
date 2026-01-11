<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

describe('ProfileCommentsComponent', function () {

    describe('Role restrictions', function () {
        it('renders empty when viewer is not authenticated', function () {
            $profileUser = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author = bob($this);

            $story = publicStory('Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

            $this->actingAs($profileUser);
            createComment('chapter', $chapter->id, generateDummyText(150));

            // Logout the user to simulate unauthenticated viewer
            auth()->logout();

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $profileUser->id]);

            // Should render empty (no content) when viewer is not authenticated
            expect($html)->toBe('');
        });

        it('renders empty when viewer is not confirmed', function () {
            $profileUser = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author = bob($this);

            $story = publicStory('Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

            $this->actingAs($profileUser);
            createComment('chapter', $chapter->id, generateDummyText(150));

            // Switch to non-confirmed viewer
            $nonConfirmedViewer = carol($this, roles: [Roles::USER]);
            $this->actingAs($nonConfirmedViewer);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $profileUser->id]);

            // Should render empty (no content) when viewer is not confirmed
            expect($html)->toBe('');
        });

        it('renders comments for confirmed viewers', function () {
            $profileUser = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author = bob($this);

            $story = publicStory('Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

            $this->actingAs($profileUser);
            createComment('chapter', $chapter->id, generateDummyText(150));

            // Confirmed viewer
            $confirmedViewer = carol($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($confirmedViewer);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $profileUser->id]);

            // Should show the comment when viewer is confirmed
            expect($html)
                ->toContain('Bob') // Author display name
                ->toContain('Test Story')
                ->toContain('story::profile.comments-count'); // Comment count translation key
        });

        it('renders comments for moderators viewing any profile', function () {
            $profileUser = alice($this, roles: [Roles::USER]); // Not confirmed
            $author = bob($this);

            $story = publicStory('Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

            $this->actingAs($profileUser);
            createComment('chapter', $chapter->id, generateDummyText(150));

            // Moderator viewer should see comments regardless of profile user's role
            $moderatorViewer = carol($this, roles: [Roles::MODERATOR]);
            $this->actingAs($moderatorViewer);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $profileUser->id]);

            // Should show the comment when viewer is moderator
            expect($html)
                ->toContain('Bob') // Author display name
                ->toContain('Test Story')
                ->toContain('story::profile.comments-count'); // Comment count translation key
        });

        it('renders comments for admins viewing any profile', function () {
            $profileUser = alice($this, roles: [Roles::USER]); // Not confirmed
            $author = bob($this);

            $story = publicStory('Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

            $this->actingAs($profileUser);
            createComment('chapter', $chapter->id, generateDummyText(150));

            // Admin viewer should see comments regardless of profile user's role
            $adminViewer = carol($this, roles: [Roles::ADMIN]);
            $this->actingAs($adminViewer);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $profileUser->id]);

            // Should show the comment when viewer is admin
            expect($html)
                ->toContain('Bob') // Author display name
                ->toContain('Test Story')
                ->toContain('story::profile.comments-count'); // Comment count translation key
        });

        it('renders comments for tech admins viewing any profile', function () {
            $profileUser = alice($this, roles: [Roles::USER]); // Not confirmed
            $author = bob($this);

            $story = publicStory('Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

            $this->actingAs($profileUser);
            createComment('chapter', $chapter->id, generateDummyText(150));

            // Tech admin viewer should see comments regardless of profile user's role
            $techAdminViewer = carol($this, roles: [Roles::TECH_ADMIN]);
            $this->actingAs($techAdminViewer);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $profileUser->id]);

            // Should show the comment when viewer is tech admin
            expect($html)
                ->toContain('Bob') // Author display name
                ->toContain('Test Story')
                ->toContain('story::profile.comments-count'); // Comment count translation key
        });

        it('renders comments when user views their own profile', function () {
            $profileUser = alice($this, roles: [Roles::USER]); // Not confirmed
            $author = bob($this);

            $story = publicStory('Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

            $this->actingAs($profileUser);
            createComment('chapter', $chapter->id, generateDummyText(150));

            // User should always see their own comments
            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $profileUser->id]);

            // Should show the comment when viewing own profile
            expect($html)
                ->toContain('Bob') // Author display name
                ->toContain('Test Story')
                ->toContain('story::profile.comments-count'); // Comment count translation key
        });

        it('renders empty state for confirmed user with no comments', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]); // Explicitly confirmed

            // Make sure the viewer is also confirmed
            $this->actingAs($user);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $user->id]);

            expect($html)->toContain(__('story::profile.no-comments'));
        });

        it('renders empty when non-confirmed user has no comments', function () {
            $user = alice($this, roles: [Roles::USER]); // Not confirmed

            // User should see their own profile (even with no comments)
            $this->actingAs($user);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $user->id]);

            // Should show "no comments" when viewing own profile, even if not confirmed
            expect($html)->toContain(__('story::profile.no-comments'));
        });
    });

    describe('Comment display', function () {
        it('displays author collapsible with story inside', function () {
            $author = alice($this);
            $commenter = bob($this);

            $story = publicStory('Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Should show author name, story title, and comment count
            expect($html)
                ->toContain('Alice') // Author display name
                ->toContain('Test Story')
                ->toContain('story::profile.comments-count'); // Comment count translation key
        });

        it('shows correct comment count for multiple chapters', function () {
            $author = alice($this);
            $commenter = bob($this);

            $story = publicStory('Multi Chapter Story', $author->id);
            $chapter1 = createPublishedChapter($this, $story, $author, ['title' => 'First Chapter']);
            $chapter2 = createPublishedChapter($this, $story, $author, ['title' => 'Second Chapter']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter1->id, generateDummyText(150));
            createComment('chapter', $chapter2->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Story should appear once with comment count under author
            expect($html)
                ->toContain('Alice')
                ->toContain('Multi Chapter Story')
                ->toContain('story::profile.comments-count'); // Comment count translation key
        });

        it('shows multiple stories under same author', function () {
            $author = alice($this);
            $commenter = bob($this);

            $story1 = publicStory('First Story', $author->id);
            $story2 = publicStory('Second Story', $author->id);
            $chapter1 = createPublishedChapter($this, $story1, $author, ['title' => 'Chapter A']);
            $chapter2 = createPublishedChapter($this, $story2, $author, ['title' => 'Chapter B']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter1->id, generateDummyText(150));
            createComment('chapter', $chapter2->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Both stories should appear under the same author
            expect($html)
                ->toContain('Alice')
                ->toContain('First Story')
                ->toContain('Second Story');
        });

        it('groups stories by author and sorts authors alphabetically', function () {
            $alice = alice($this);
            $bob = bob($this);
            $commenter = registerUserThroughForm($this, ['name' => 'Commenter', 'email' => 'commenter@example.com'], true, [Roles::USER_CONFIRMED]);

            $aliceStory = publicStory('Alice Story', $alice->id);
            $bobStory = publicStory('Bob Story', $bob->id);
            $aliceChapter = createPublishedChapter($this, $aliceStory, $alice, ['title' => 'Alice Chapter']);
            $bobChapter = createPublishedChapter($this, $bobStory, $bob, ['title' => 'Bob Chapter']);

            $this->actingAs($commenter);
            createComment('chapter', $aliceChapter->id, generateDummyText(150));
            createComment('chapter', $bobChapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Both authors should appear with their stories
            expect($html)
                ->toContain('Alice')
                ->toContain('Alice Story')
                ->toContain('Bob')
                ->toContain('Bob Story');

            // Alice should appear before Bob (alphabetical order)
            $alicePos = strpos($html, 'Alice');
            $bobPos = strpos($html, 'Bob');
            expect($alicePos)->toBeLessThan($bobPos);
        });
    });

    describe('Co-authored stories', function () {
        it('groups co-authored stories under a single collapsible with both authors', function () {
            $alice = alice($this);
            $bob = bob($this);
            $commenter = registerUserThroughForm($this, ['name' => 'Commenter', 'email' => 'commenter@example.com'], true, [Roles::USER_CONFIRMED]);

            // Create a co-authored story
            $coAuthoredStory = publicStory('Co-Authored Story', $alice->id);
            addCollaborator($coAuthoredStory->id, $bob->id, 'author');
            $chapter = createPublishedChapter($this, $coAuthoredStory, $alice, ['title' => 'Co-Authored Chapter']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Both author names should appear in the same collapsible header
            expect($html)
                ->toContain('Alice')
                ->toContain('Bob')
                ->toContain('Co-Authored Story');

            // Story should only appear once (not duplicated under each author)
            $storyCount = substr_count($html, 'Co-Authored Story');
            expect($storyCount)->toBe(1);
        });

        it('displays multiple co-authored stories by same authors in one group', function () {
            $alice = alice($this);
            $bob = bob($this);
            $commenter = registerUserThroughForm($this, ['name' => 'Commenter', 'email' => 'commenter@example.com'], true, [Roles::USER_CONFIRMED]);

            // Create two co-authored stories by the same pair
            $story1 = publicStory('First Co-Authored', $alice->id);
            addCollaborator($story1->id, $bob->id, 'author');
            $chapter1 = createPublishedChapter($this, $story1, $alice, ['title' => 'Chapter 1']);

            $story2 = publicStory('Second Co-Authored', $alice->id);
            addCollaborator($story2->id, $bob->id, 'author');
            $chapter2 = createPublishedChapter($this, $story2, $alice, ['title' => 'Chapter 2']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter1->id, generateDummyText(150));
            createComment('chapter', $chapter2->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Both stories should appear
            expect($html)
                ->toContain('First Co-Authored')
                ->toContain('Second Co-Authored');

            // Each story should appear exactly once (both under the same author group)
            expect(substr_count($html, 'First Co-Authored'))->toBe(1);
            expect(substr_count($html, 'Second Co-Authored'))->toBe(1);
        });

        it('separates stories with different co-author sets into different groups', function () {
            $alice = alice($this);
            $bob = bob($this);
            $charlie = registerUserThroughForm($this, ['name' => 'Charlie', 'email' => 'charlie@example.com'], true, [Roles::USER_CONFIRMED]);
            $commenter = registerUserThroughForm($this, ['name' => 'Commenter', 'email' => 'commenter@example.com'], true, [Roles::USER_CONFIRMED]);

            // Story by Alice alone
            $aliceStory = publicStory('Alice Solo Story', $alice->id);
            $aliceChapter = createPublishedChapter($this, $aliceStory, $alice, ['title' => 'Alice Chapter']);

            // Story by Alice + Bob
            $aliceBobStory = publicStory('Alice Bob Story', $alice->id);
            addCollaborator($aliceBobStory->id, $bob->id, 'author');
            $aliceBobChapter = createPublishedChapter($this, $aliceBobStory, $alice, ['title' => 'Alice Bob Chapter']);

            // Story by Alice + Charlie
            $aliceCharlieStory = publicStory('Alice Charlie Story', $alice->id);
            addCollaborator($aliceCharlieStory->id, $charlie->id, 'author');
            $aliceCharlieChapter = createPublishedChapter($this, $aliceCharlieStory, $alice, ['title' => 'Alice Charlie Chapter']);

            $this->actingAs($commenter);
            createComment('chapter', $aliceChapter->id, generateDummyText(150));
            createComment('chapter', $aliceBobChapter->id, generateDummyText(150));
            createComment('chapter', $aliceCharlieChapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // All three stories should appear
            expect($html)
                ->toContain('Alice Solo Story')
                ->toContain('Alice Bob Story')
                ->toContain('Alice Charlie Story');

            // Each story should appear exactly once
            expect(substr_count($html, 'Alice Solo Story'))->toBe(1);
            expect(substr_count($html, 'Alice Bob Story'))->toBe(1);
            expect(substr_count($html, 'Alice Charlie Story'))->toBe(1);
        });

        it('displays co-author names separated by commas', function () {
            $alice = alice($this);
            $bob = bob($this);
            $commenter = registerUserThroughForm($this, ['name' => 'Commenter', 'email' => 'commenter@example.com'], true, [Roles::USER_CONFIRMED]);

            $coAuthoredStory = publicStory('Comma Test Story', $alice->id);
            addCollaborator($coAuthoredStory->id, $bob->id, 'author');
            $chapter = createPublishedChapter($this, $coAuthoredStory, $alice, ['title' => 'Test Chapter']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Authors should be comma-separated (Alice, Bob or Bob, Alice depending on sort)
            expect($html)->toMatch('/Alice.*,.*Bob|Bob.*,.*Alice/');
        });

        it('sorts co-authors alphabetically within a group', function () {
            $zara = registerUserThroughForm($this, ['name' => 'Zara', 'email' => 'zara@example.com'], true, [Roles::USER_CONFIRMED]);
            $alice = alice($this);
            $commenter = registerUserThroughForm($this, ['name' => 'Commenter', 'email' => 'commenter@example.com'], true, [Roles::USER_CONFIRMED]);

            // Create story with Zara as primary author, Alice as co-author
            $story = publicStory('Alphabetical Test', $zara->id);
            addCollaborator($story->id, $alice->id, 'author');
            $chapter = createPublishedChapter($this, $story, $zara, ['title' => 'Test Chapter']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Alice should appear before Zara (alphabetical)
            $alicePos = strpos($html, 'Alice');
            $zaraPos = strpos($html, 'Zara');
            expect($alicePos)->toBeLessThan($zaraPos);
        });
    });

    describe('Visibility filtering', function () {
        it('excludes comments on private stories', function () {
            $author = alice($this);
            $commenter = bob($this);

            $publicStory = publicStory('Public Story', $author->id);
            $privateStory = privateStory('Private Story', $author->id);

            $publicChapter = createPublishedChapter($this, $publicStory, $author, ['title' => 'Public Chapter']);
            $privateChapter = createPublishedChapter($this, $privateStory, $author, ['title' => 'Private Chapter']);

            addCollaborator($privateStory->id, $commenter->id, 'betareader');

            $this->actingAs($commenter);
            createComment('chapter', $publicChapter->id, generateDummyText(150));
            createComment('chapter', $privateChapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            expect($html)
                ->toContain('Public Story')
                ->not->toContain('Private Story');
        });

        it('includes comments on community stories', function () {
            $author = alice($this);
            $commenter = bob($this);

            $communityStory = communityStory('Community Story', $author->id);
            $chapter = createPublishedChapter($this, $communityStory, $author, ['title' => 'Community Chapter']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            expect($html)->toContain('Community Story');
        });

        it('excludes comments on unpublished chapters from count', function () {
            $author = alice($this);
            $commenter = bob($this);

            $story = publicStory('Story With Mixed Chapters', $author->id);
            $publishedChapter = createPublishedChapter($this, $story, $author, ['title' => 'Published Chapter']);
            $unpublishedChapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Unpublished Chapter']);

            $this->actingAs($commenter);
            createComment('chapter', $publishedChapter->id, generateDummyText(150));
            createComment('chapter', $unpublishedChapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Should show story with count (only published chapter counted)
            expect($html)
                ->toContain('Story With Mixed Chapters')
                ->toContain('story::profile.comments-count'); // Comment count translation key
        });
    });

    describe('Edge cases', function () {
        it('does not include replies (only root comments)', function () {
            $author = alice($this);
            $commenter = bob($this);
            $replier = registerUserThroughForm($this, ['name' => 'Charlie', 'email' => 'charlie@example.com'], true, [Roles::USER_CONFIRMED]);

            $story = publicStory('Reply Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Reply Chapter']);

            $this->actingAs($commenter);
            $rootId = createComment('chapter', $chapter->id, generateDummyText(150));

            $this->actingAs($replier);
            createComment('chapter', $chapter->id, generateDummyText(150), $rootId);

            // Replier's profile should show empty state (only has a reply, no root comments)
            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $replier->id]);

            expect($html)->toContain(__('story::profile.no-comments'));
        });

        it('shows empty state when all commented chapters become unpublished', function () {
            $author = alice($this);
            $commenter = bob($this);

            $story = publicStory('Unpublish Test', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Will Be Unpublished']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            Chapter::where('id', $chapter->id)->update(['status' => Chapter::STATUS_NOT_PUBLISHED]);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            expect($html)->toContain(__('story::profile.no-comments'));
        });

        it('shows empty state when all commented stories become private', function () {
            $author = alice($this);
            $commenter = bob($this);

            $story = publicStory('Will Be Private', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            Story::where('id', $story->id)->update(['visibility' => Story::VIS_PRIVATE]);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            expect($html)->toContain(__('story::profile.no-comments'));
        });
    });
});

describe('ProfileCommentsApiController', function () {
    it('returns comments for a story by user', function () {
        $author = alice($this);
        $commenter = bob($this);

        $story = publicStory('API Test Story', $author->id);
        $chapter1 = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);
        $chapter2 = createPublishedChapter($this, $story, $author, ['title' => 'Chapter Two']);

        $this->actingAs($commenter);
        createComment('chapter', $chapter1->id, generateDummyText(150));
        createComment('chapter', $chapter2->id, generateDummyText(150));

        $response = $this->getJson(route('profile.comments.api', ['storyId' => $story->id, 'userId' => $commenter->id]));

        $response->assertOk();
        $response->assertJsonCount(2, 'comments');
        $response->assertJsonPath('comments.0.chapterTitle', 'Chapter One');
        $response->assertJsonPath('comments.1.chapterTitle', 'Chapter Two');
    });

    it('returns empty array for user with no comments on story', function () {
        $author = alice($this);
        $commenter = bob($this);

        $story = publicStory('Empty Story', $author->id);
        createPublishedChapter($this, $story, $author, ['title' => 'Chapter']);

        $this->actingAs($commenter);

        $response = $this->getJson(route('profile.comments.api', ['storyId' => $story->id, 'userId' => $commenter->id]));

        $response->assertOk();
        $response->assertJsonCount(0, 'comments');
    });

    it('excludes unpublished chapters from API response', function () {
        $author = alice($this);
        $commenter = bob($this);

        $story = publicStory('Mixed Chapters Story', $author->id);
        $publishedChapter = createPublishedChapter($this, $story, $author, ['title' => 'Published']);
        $unpublishedChapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Unpublished']);

        $this->actingAs($commenter);
        createComment('chapter', $publishedChapter->id, generateDummyText(150));
        createComment('chapter', $unpublishedChapter->id, generateDummyText(150));

        $response = $this->getJson(route('profile.comments.api', ['storyId' => $story->id, 'userId' => $commenter->id]));

        $response->assertOk();
        $response->assertJsonCount(1, 'comments');
        $response->assertJsonPath('comments.0.chapterTitle', 'Published');
    });

    it('orders comments by chapter sort_order', function () {
        $author = alice($this);
        $commenter = bob($this);

        $story = publicStory('Ordered Story', $author->id);
        $chapter2 = createPublishedChapter($this, $story, $author, ['title' => 'Second']);
        $chapter1 = createPublishedChapter($this, $story, $author, ['title' => 'First']);

        Chapter::where('id', $chapter1->id)->update(['sort_order' => 100]);
        Chapter::where('id', $chapter2->id)->update(['sort_order' => 200]);

        $this->actingAs($commenter);
        createComment('chapter', $chapter1->id, generateDummyText(150));
        createComment('chapter', $chapter2->id, generateDummyText(150));

        $response = $this->getJson(route('profile.comments.api', ['storyId' => $story->id, 'userId' => $commenter->id]));

        $response->assertOk();
        $response->assertJsonPath('comments.0.chapterTitle', 'First');
        $response->assertJsonPath('comments.1.chapterTitle', 'Second');
    });
});
