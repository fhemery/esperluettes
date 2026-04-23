<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Public\Events\ModeratorAccessedPrivateChapter;
use App\Domains\Story\Public\Events\ModeratorAccessedPrivateStory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Story Moderation Admin - Access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('story.admin.moderation.index'))
            ->assertRedirect('/login');
    });

    it('denies user-confirmed access', function () {
        $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]))
            ->get(route('story.admin.moderation.index'))
            ->assertRedirect(route('dashboard'));
    });

    it('grants moderator access', function () {
        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.index'))
            ->assertOk();
    });

    it('grants admin access', function () {
        $this->actingAs(admin($this))
            ->get(route('story.admin.moderation.index'))
            ->assertOk();
    });
});

describe('Story Moderation Admin - Index', function () {
    it('lists all stories regardless of visibility', function () {
        $author = alice($this);
        publicStory('Public Story', $author->id);
        privateStory('Private Story', $author->id);
        communityStory('Community Story', $author->id);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.index'))
            ->assertOk()
            ->assertSee('Public Story')
            ->assertSee('Private Story')
            ->assertSee('Community Story');
    });

    it('filters stories by title search', function () {
        $author = alice($this);
        publicStory('Narnia Chronicles', $author->id);
        publicStory('Lord of the Rings', $author->id);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.index', ['search' => 'Narnia']))
            ->assertOk()
            ->assertSee('Narnia Chronicles')
            ->assertDontSee('Lord of the Rings');
    });

    it('shows no link for a solo private story (single author, no other collaborators)', function () {
        $author = alice($this);
        $story = privateStory('Secret Story', $author->id);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.index'))
            ->assertOk()
            ->assertSee('Secret Story')
            ->assertDontSee(route('story.admin.moderation.story-access', $story->id))
            ->assertDontSee(route('stories.show', $story->slug));
    });

    it('shows audit link for a private story with two or more collaborators', function () {
        $author = alice($this);
        $coauthor = bob($this);
        $story = privateStory('Shared Private', $author->id);
        addCollaborator($story->id, $coauthor->id, 'author');

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.index'))
            ->assertOk()
            ->assertSee(route('story.admin.moderation.story-access', $story->id));
    });

    it('shows normal link for public stories', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.index'))
            ->assertOk()
            ->assertSee(route('stories.show', $story->slug));
    });
});

describe('Story Moderation Admin - Chapters partial', function () {
    it('returns chapters HTML for a story', function () {
        $author = alice($this);
        $story = publicStory('Story With Chapters', $author->id);
        createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);
        createUnpublishedChapter($this, $story, $author, ['title' => 'Draft Chapter']);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.chapters', $story->id))
            ->assertOk()
            ->assertSee('Chapter One')
            ->assertSee('Draft Chapter');
    });

    it('shows unpublished chapters that would normally be hidden', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        createUnpublishedChapter($this, $story, $author, ['title' => 'Hidden Draft']);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.chapters', $story->id))
            ->assertOk()
            ->assertSee('Hidden Draft');
    });

    it('shows audit link for unpublished chapters when moderator is not author (multi-author story)', function () {
        $author = alice($this);
        $coauthor = bob($this);
        $story = publicStory('Story', $author->id);
        addCollaborator($story->id, $coauthor->id, 'author');
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Draft']);

        $this->actingAs(moderator($this)) // not the author
            ->get(route('story.admin.moderation.chapters', $story->id))
            ->assertOk()
            ->assertSee(route('story.admin.moderation.chapter-access', $chapter->id));
    });

    it('shows normal link for published chapters of public story', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Published']);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.chapters', $story->id))
            ->assertOk()
            ->assertSee(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    });

    it('shows audit link for all chapters of private story when moderator is not collaborator', function () {
        $author = alice($this);
        $story = privateStory('Secret', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Published in Private']);

        $this->actingAs(moderator($this)) // not a collaborator
            ->get(route('story.admin.moderation.chapters', $story->id))
            ->assertOk()
            ->assertSee(route('story.admin.moderation.chapter-access', $chapter->id));
    });

    it('denies non-moderators access to chapters endpoint', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);

        $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]))
            ->get(route('story.admin.moderation.chapters', $story->id))
            ->assertRedirect(route('dashboard'));
    });
});

describe('Story Moderation Admin - Story access (audit)', function () {
    it('emits ModeratorAccessedPrivateStory event and redirects to story', function () {
        $author = alice($this);
        $story = privateStory('Secret Story', $author->id);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.story-access', $story->id))
            ->assertRedirect(route('stories.show', $story->slug));

        /** @var ModeratorAccessedPrivateStory $event */
        $event = latestEventOf(ModeratorAccessedPrivateStory::name(), ModeratorAccessedPrivateStory::class);
        expect($event)->not->toBeNull();
        expect($event->storyId)->toBe($story->id);
        expect($event->title)->toBe($story->title);
    });

    it('denies non-moderators access to story audit route', function () {
        $author = alice($this);
        $story = privateStory('Secret', $author->id);

        $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]))
            ->get(route('story.admin.moderation.story-access', $story->id))
            ->assertRedirect(route('dashboard'));
    });
});

describe('Story Moderation Admin - Chapter access (audit)', function () {
    it('emits ModeratorAccessedPrivateChapter event and redirects to chapter', function () {
        $author = alice($this);
        $story = privateStory('Secret Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Hidden Chapter']);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.chapter-access', $chapter->id))
            ->assertRedirect(route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]));

        /** @var ModeratorAccessedPrivateChapter $event */
        $event = latestEventOf(ModeratorAccessedPrivateChapter::name(), ModeratorAccessedPrivateChapter::class);
        expect($event)->not->toBeNull();
        expect($event->chapterId)->toBe($chapter->id);
        expect($event->storyId)->toBe($story->id);
    });

    it('denies non-moderators access to chapter audit route', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author);

        $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]))
            ->get(route('story.admin.moderation.chapter-access', $chapter->id))
            ->assertRedirect(route('dashboard'));
    });
});

describe('Story Moderation Admin - Pagination', function () {
    it('renders without error when stories exceed one page', function () {
        $author = alice($this);
        for ($i = 1; $i <= 21; $i++) {
            publicStory("Story $i", $author->id);
        }

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.index'))
            ->assertOk()
            ->assertSee('Story 1');
    });
});

describe('Story Moderation Admin - Chapters button visibility', function () {
    it('hides the chapters button for solo private stories', function () {
        $author = alice($this);
        privateStory('Solo Private', $author->id);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.index'))
            ->assertOk()
            ->assertDontSee(__('story::admin.moderation.chapters_button'));
    });

    it('shows the chapters button for private stories with multiple collaborators', function () {
        $author = alice($this);
        $coauthor = bob($this);
        $story = privateStory('Shared Private', $author->id);
        addCollaborator($story->id, $coauthor->id, 'author');

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.index'))
            ->assertOk()
            ->assertSee(__('story::admin.moderation.chapters_button'));
    });
});

describe('Story Moderation Admin - Unpublished chapter access', function () {
    it('shows no link for an unpublished chapter in a solo-author story', function () {
        $author = alice($this);
        $story = publicStory('Solo Author Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Draft']);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.chapters', $story->id))
            ->assertOk()
            ->assertSee('Draft')
            ->assertDontSee(route('story.admin.moderation.chapter-access', $chapter->id))
            ->assertDontSee(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    });

    it('shows audit link for an unpublished chapter in a multi-author story', function () {
        $author = alice($this);
        $coauthor = bob($this);
        $story = publicStory('Multi Author Story', $author->id);
        addCollaborator($story->id, $coauthor->id, 'author');
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Hidden Draft']);

        $this->actingAs(moderator($this))
            ->get(route('story.admin.moderation.chapters', $story->id))
            ->assertOk()
            ->assertSee('Hidden Draft')
            ->assertSee(route('story.admin.moderation.chapter-access', $chapter->id));
    });
});

describe('Story policy - Moderator bypass', function () {
    it('allows moderator to view a private story page', function () {
        $author = alice($this);
        $story = privateStory('Private Story', $author->id);

        $this->actingAs(moderator($this))
            ->get(route('stories.show', $story->slug))
            ->assertOk();
    });

    it('returns 404 for non-collaborator user-confirmed on private story', function () {
        $author = alice($this);
        $story = privateStory('Private Story', $author->id);

        $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]))
            ->get(route('stories.show', $story->slug))
            ->assertNotFound();
    });
});

describe('Chapter policy - Moderator bypass', function () {
    it('allows moderator to view an unpublished chapter', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Draft']);

        $this->actingAs(moderator($this))
            ->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
            ->assertOk();
    });

    it('returns 404 for non-author user-confirmed on unpublished chapter', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author);

        $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]))
            ->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
            ->assertNotFound();
    });
});
