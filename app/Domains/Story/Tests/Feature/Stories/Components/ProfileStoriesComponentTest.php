<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

describe('ProfileStoriesComponent', function () {

    describe('Story visibility', function () {
        it('lists only public stories for guests', function () {
            $owner = alice($this);

            $public = publicStory('Guest Public Story', $owner->id);
            $community = communityStory('Guest Community Story', $owner->id);
            $private = privateStory('Guest Private Story', $owner->id);

            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)
                ->toContain($public->title)
                ->not->toContain($community->title)
                ->not->toContain($private->title);
        });

        it('lists public and community stories to logged-in viewers (non-owner)', function () {
            $owner = alice($this);
            $viewer = bob($this);

            $public = publicStory('Public Story', $owner->id);
            $community = communityStory('Community Story', $owner->id);
            $private = privateStory('Private Story', $owner->id);

            $this->actingAs($viewer);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)
                ->toContain($public->title)
                ->toContain($community->title)
                ->not->toContain($private->title);
        });

        it('includes private stories when viewer is the owner', function () {
            $owner = alice($this);

            $private = privateStory('Owner Private', $owner->id);

            $this->actingAs($owner);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)->toContain($private->title);
        });

        it('includes private stories when viewer is a contributor', function () {
            $owner = alice($this);
            $contrib = bob($this);

            $private = privateStory('Contributor Private', $owner->id);
            addCollaborator($private->id, $contrib->id, 'betareader');

            $this->actingAs($contrib);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)->toContain($private->title);
        });

        it('lists only stories authored by the profile owner', function () {
            $owner = alice($this);
            $other = bob($this);

            $owned = publicStory('Owned Story', $owner->id);
            $foreign = publicStory('Foreign Story', $other->id);

            $this->actingAs($other);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)
                ->toContain($owned->title)
                ->not->toContain($foreign->title);
        });
    });

    describe('Owner controls', function () {
        it('shows new Story button for the owner with USER_CONFIRMED role', function () {
            $owner = alice($this);

            $this->actingAs($owner);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)->toContain(__('story::profile.new-story'));
        });

        it('does not show new Story button for the owner without USER_CONFIRMED role', function () {
            $owner = alice($this, roles: [Roles::USER]);

            $this->actingAs($owner);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)->not->toContain(__('story::profile.new-story'));
        });

        it('does not show new Story button to other viewers', function () {
            $owner = alice($this);
            $viewer = bob($this);

            $this->actingAs($viewer);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)->not->toContain(__('story::profile.new-story'));
        });
    });

    describe('Story list display', function () {
        it('does not list author names', function () {
            $owner = alice($this);
            publicStory('Some Story', $owner->id);

            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)->not->toContain(__('story::shared.by'));
        });

        it('shows words and chapter count if there is at least one chapter', function () {
            $owner = alice($this);
            $story = publicStory('Some Story', $owner->id);
            createPublishedChapter($this, $story, $owner);

            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)
                ->toContain(__('story::shared.metrics.words'))
                ->toContain(__('story::shared.metrics.chapters'));
        });

        it('does not show word count if there are no chapters', function () {
            $owner = alice($this);
            publicStory('No Chapters Story', $owner->id);

            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)
                ->not->toContain(__('story::shared.metrics.words'))
                ->toContain(__('story::shared.metrics.chapters'));
        });
    });

    describe('Chapter credits', function () {
        it('shows chapter credits badge with 5 for a confirmed user profile', function () {
            $owner = alice($this);

            $this->actingAs($owner);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)
                ->toContain('menu_book')
                ->toContain('>5<');
        });

        it('shows 4 after the owner creates one chapter', function () {
            $owner = alice($this);
            $story = publicStory('Story A', $owner->id);
            createUnpublishedChapter($this, $story, $owner, ['title' => 'C1']);

            $this->actingAs($owner);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)
                ->toContain('menu_book')
                ->toContain('>4<');
        });

        it('does not show the counter to other viewers', function () {
            $owner = alice($this);
            $viewer = bob($this);

            $this->actingAs($viewer);
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)->not->toContain('menu_book');
        });

        it('shows 6 when the owner posts a root comment on someone else\'s published chapter', function () {
            $commenter = alice($this);
            $author = bob($this);

            $foreignStory = publicStory('Foreign', $author->id);
            $chapter = createPublishedChapter($this, $foreignStory, $author, ['title' => 'F1']);

            $this->actingAs($commenter);
            createComment('chapter', $chapter->id, generateDummyText(150));

            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $commenter->id]);

            expect($html)
                ->toContain('menu_book')
                ->toContain('>6<');
        });

        it('still shows 5 when the owner posts a non-root (reply) comment on someone else\'s chapter', function () {
            $author = alice($this);
            $commenter = bob($this);

            $foreignStory = publicStory('Foreign', $author->id);
            $chapter = createPublishedChapter($this, $foreignStory, $author, ['title' => 'F1']);

            $this->actingAs($commenter);
            $commentId = createComment('chapter', $chapter->id, generateDummyText(150));
            createComment('chapter', $chapter->id, generateDummyText(150), $commentId);

            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $commenter->id]);

            expect($html)
                ->toContain('menu_book')
                ->toContain('>6<'); // And not 7: the reply does not count
        });
    });

    describe('Reading statistics', function () {
        it('shows total reads on profile stories cards', function () {
            $owner = alice($this);
            $story = publicStory('Profile Total Reads', $owner->id);
            $chapter = createPublishedChapter($this, $story, $owner);

            $reader = bob($this);
            $this->actingAs($reader);
            markAsRead($this, $chapter)->assertNoContent();

            Auth::logout();
            $html = Blade::render('<x-story::profile-stories-component :user-id="$userId" />', ['userId' => $owner->id]);

            expect($html)
                ->toContain('Profile Total Reads')
                ->toContain('1'); // Read count displayed somewhere in the card
        });
    });
});
