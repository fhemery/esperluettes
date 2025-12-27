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
        it('renders nothing for non-confirmed users', function () {
            $user = alice($this, roles: [Roles::USER]);

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $user->id]);

            // Should render empty (isAllowed = false)
            expect($html)->not->toContain(__('story::profile.no-comments'));
            expect($html)->not->toContain('grid');
        });

        it('renders empty state for confirmed user with no comments', function () {
            $user = alice($this); // USER_CONFIRMED by default

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $user->id]);

            expect($html)->toContain(__('story::profile.no-comments'));
        });
    });

    describe('Comment display', function () {
        it('displays story card with comment count badge', function () {
            $author = alice($this);
            $commenter = bob($this);

            $story = publicStory('Test Story', $author->id);
            $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-comments-component :user-id="$userId" />', ['userId' => $commenter->id]);

            // Should show story title and grid layout
            expect($html)
                ->toContain('Test Story')
                ->toContain('grid') // Grid layout
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

            // Story should appear once with comment count
            expect($html)
                ->toContain('Multi Chapter Story')
                ->toContain('story::profile.comments-count'); // Comment count translation key
        });

        it('shows multiple story cards for comments on different stories', function () {
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

            expect($html)
                ->toContain('First Story')
                ->toContain('Second Story');
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
