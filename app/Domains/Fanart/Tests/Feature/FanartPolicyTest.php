<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartistService;
use App\Domains\Fanart\Private\Services\ModeService;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** Fresh instance each call: SettingsService keeps a per-instance request cache that tests would otherwise read stale. */
function fanartPolicy(): FanartPolicy
{
    app()->forgetInstance(FanartPolicy::class);

    return app(FanartPolicy::class);
}

function makeFanartist(int $storyId, int $userId): void
{
    Fanartist::create(['story_id' => $storyId, 'user_id' => $userId, 'granted_by_user_id' => $userId]);
}

describe('fanartiste membership reads', function () {
    it('reads membership and lists fanartiste ids', function () {
        $author = alice($this);
        $story = publicStory('Histoire', $author->id);
        $fan = bob($this);
        $other = carol($this);
        makeFanartist($story->id, $fan->id);

        $service = app(FanartistService::class);

        expect($service->isFanartist($fan->id, $story->id))->toBeTrue()
            ->and($service->isFanartist($other->id, $story->id))->toBeFalse()
            ->and($service->listFanartistIds($story->id))->toBe([$fan->id]);
    });
});

describe('seeFeature', function () {
    it('denies every ability to a guest and to a non-confirmed user', function () {
        $author = alice($this);
        $story = publicStory('Histoire', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $fanart = createFanart(['story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => $author->id]);
        $plain = bob($this, roles: [Roles::USER]);

        foreach ([null, $plain->id] as $id) {
            expect(fanartPolicy()->seeFeature($id, $story->id))->toBeFalse()
                ->and(fanartPolicy()->view($id, $fanart))->toBeFalse()
                ->and(fanartPolicy()->streamFile($id, $fanart))->toBeFalse()
                ->and(fanartPolicy()->submit($id, $story->id, FanartKind::Image))->toBeFalse()
                ->and(fanartPolicy()->uploadArtwork($id, $story->id))->toBeFalse()
                ->and(fanartPolicy()->editArtwork($id, $fanart))->toBeFalse()
                ->and(fanartPolicy()->review($id, $fanart))->toBeFalse()
                ->and(fanartPolicy()->delete($id, $fanart))->toBeFalse()
                ->and(fanartPolicy()->moderate($id, $fanart))->toBeFalse()
                ->and(fanartPolicy()->manageFanartists($id, $story->id))->toBeFalse()
                ->and(fanartPolicy()->requestRole($id, $story->id))->toBeFalse();
        }
    });

    it('hides everything on a private story from a confirmed non-collaborator, shows it to a moderator', function () {
        $author = alice($this);
        $story = privateStory('Secrete', $author->id);
        $fanart = createFanart(['story_id' => $story->id, 'user_id' => $author->id]);
        $reader = bob($this);
        $mod = moderator($this);

        expect(fanartPolicy()->seeFeature($reader->id, $story->id))->toBeFalse()
            ->and(fanartPolicy()->view($reader->id, $fanart))->toBeFalse()
            ->and(fanartPolicy()->seeFeature($mod->id, $story->id))->toBeTrue()
            ->and(fanartPolicy()->view($mod->id, $fanart))->toBeTrue()
            ->and(fanartPolicy()->seeFeature($author->id, $story->id))->toBeTrue();
    });

    it('hides everything once the story is soft-deleted (author deactivated)', function () {
        $author = alice($this);
        $story = publicStory('Histoire', $author->id);
        $fanart = createFanart(['story_id' => $story->id, 'user_id' => $author->id]);
        $reader = bob($this);
        $mod = moderator($this);

        Story::query()->whereKey($story->id)->first()->delete();

        expect(fanartPolicy()->view($reader->id, $fanart))->toBeFalse()
            ->and(fanartPolicy()->view($author->id, $fanart))->toBeFalse()
            ->and(fanartPolicy()->view($mod->id, $fanart))->toBeFalse();
    });
});

describe('view', function () {
    it('lets a confirmed reader view a published fanart on a public and a community story', function () {
        $author = alice($this);
        $reader = bob($this);

        foreach ([publicStory('Pub', $author->id), communityStory('Com', $author->id)] as $story) {
            $fanart = createFanart(['story_id' => $story->id, 'user_id' => $author->id]);
            expect(fanartPolicy()->view($reader->id, $fanart))->toBeTrue()
                ->and(fanartPolicy()->streamFile($reader->id, $fanart))->toBeTrue();
        }
    });

    it('hides a published fanart on an unpublished chapter from readers but not from authors or staff', function () {
        $author = alice($this);
        $story = publicStory('Histoire', $author->id);
        $draft = createUnpublishedChapter($this, $story, $author);
        $fanart = createFanart(['story_id' => $story->id, 'chapter_id' => $draft->id, 'user_id' => $author->id]);
        $reader = bob($this);
        $mod = moderator($this);

        expect(fanartPolicy()->view($reader->id, $fanart))->toBeFalse()
            ->and(fanartPolicy()->view($author->id, $fanart))->toBeTrue()
            ->and(fanartPolicy()->view($mod->id, $fanart))->toBeTrue();
    });

    it('shows a pending fanart only to authors, its submitter and staff', function () {
        $author = alice($this);
        $coAuthor = bob($this);
        $story = publicStory('Histoire', $author->id);
        addCollaborator($story->id, $coAuthor->id);
        $submitter = carol($this);
        $stranger = daniel($this);
        $mod = moderator($this);
        $fanart = createFanart([
            'story_id' => $story->id,
            'user_id' => $submitter->id,
            'status' => FanartStatus::Pending,
            'published_at' => null,
        ]);

        expect(fanartPolicy()->view($author->id, $fanart))->toBeTrue()
            ->and(fanartPolicy()->view($coAuthor->id, $fanart))->toBeTrue()
            ->and(fanartPolicy()->view($submitter->id, $fanart))->toBeTrue()
            ->and(fanartPolicy()->view($mod->id, $fanart))->toBeTrue()
            ->and(fanartPolicy()->view($stranger->id, $fanart))->toBeFalse();
    });
});

describe('submit', function () {
    it('allows submit only per effective mode: yes, designated+fanartiste, never no', function () {
        $author = alice($this);
        $story = publicStory('Histoire', $author->id);
        $fan = bob($this);
        $reader = carol($this);
        makeFanartist($story->id, $fan->id);

        setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'yes');
        expect(fanartPolicy()->submit($reader->id, $story->id, FanartKind::Image))->toBeTrue();

        setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'designated');
        expect(fanartPolicy()->submit($reader->id, $story->id, FanartKind::Image))->toBeFalse()
            ->and(fanartPolicy()->submit($fan->id, $story->id, FanartKind::Image))->toBeTrue()
            ->and(app(ModeService::class)->canSubmit($fan->id, $story->id, FanartKind::Image))->toBeTrue();

        setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'no');
        expect(fanartPolicy()->submit($reader->id, $story->id, FanartKind::Image))->toBeFalse()
            ->and(fanartPolicy()->submit($fan->id, $story->id, FanartKind::Image))->toBeFalse();
    });

    it('refuses submit on a private story even to a fanartiste', function () {
        $author = alice($this);
        $story = privateStory('Secrete', $author->id);
        $fan = bob($this);
        makeFanartist($story->id, $fan->id);
        addCollaborator($story->id, $fan->id, 'beta_reader');
        setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'yes');

        expect(fanartPolicy()->submit($fan->id, $story->id, FanartKind::Image))->toBeFalse();
    });
});

describe('artwork and author abilities', function () {
    it('allows uploadArtwork only to an author, never on a private story', function () {
        $author = alice($this);
        $coAuthor = bob($this);
        $reader = carol($this);
        $public = publicStory('Pub', $author->id);
        addCollaborator($public->id, $coAuthor->id);
        $private = privateStory('Priv', $author->id);

        expect(fanartPolicy()->uploadArtwork($author->id, $public->id))->toBeTrue()
            ->and(fanartPolicy()->uploadArtwork($coAuthor->id, $public->id))->toBeTrue()
            ->and(fanartPolicy()->uploadArtwork($reader->id, $public->id))->toBeFalse()
            ->and(fanartPolicy()->uploadArtwork($author->id, $private->id))->toBeFalse();
    });

    it('allows editArtwork only to an author, on an artwork', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Histoire', $author->id);
        $artwork = createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'is_artwork' => true]);
        $fanart = createFanart(['story_id' => $story->id, 'user_id' => $reader->id]);

        expect(fanartPolicy()->editArtwork($author->id, $artwork))->toBeTrue()
            ->and(fanartPolicy()->editArtwork($reader->id, $artwork))->toBeFalse()
            ->and(fanartPolicy()->editArtwork($author->id, $fanart))->toBeFalse();
    });

    it('allows review only to an author of the story on a pending fanart', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Histoire', $author->id);
        $pending = createFanart([
            'story_id' => $story->id, 'user_id' => $reader->id,
            'status' => FanartStatus::Pending, 'published_at' => null,
        ]);
        $published = createFanart(['story_id' => $story->id, 'user_id' => $reader->id]);
        $mod = moderator($this);

        expect(fanartPolicy()->review($author->id, $pending))->toBeTrue()
            ->and(fanartPolicy()->review($reader->id, $pending))->toBeFalse()
            ->and(fanartPolicy()->review($mod->id, $pending))->toBeFalse()
            ->and(fanartPolicy()->review($author->id, $published))->toBeFalse();
    });

    it('allows delete to the submitter (any status) and to an author (published)', function () {
        $author = alice($this);
        $reader = bob($this);
        $other = carol($this);
        $story = publicStory('Histoire', $author->id);
        $pending = createFanart([
            'story_id' => $story->id, 'user_id' => $reader->id,
            'status' => FanartStatus::Pending, 'published_at' => null,
        ]);
        $published = createFanart(['story_id' => $story->id, 'user_id' => $reader->id]);

        expect(fanartPolicy()->delete($reader->id, $pending))->toBeTrue()
            ->and(fanartPolicy()->delete($reader->id, $published))->toBeTrue()
            ->and(fanartPolicy()->delete($author->id, $published))->toBeTrue()
            ->and(fanartPolicy()->delete($author->id, $pending))->toBeFalse()
            ->and(fanartPolicy()->delete($other->id, $published))->toBeFalse();
    });

    it('allows moderate to staff only', function () {
        $author = alice($this);
        $story = publicStory('Histoire', $author->id);
        $fanart = createFanart(['story_id' => $story->id, 'user_id' => $author->id]);

        expect(fanartPolicy()->moderate(moderator($this)->id, $fanart))->toBeTrue()
            ->and(fanartPolicy()->moderate(admin($this)->id, $fanart))->toBeTrue()
            ->and(fanartPolicy()->moderate($author->id, $fanart))->toBeFalse()
            ->and(fanartPolicy()->moderate(bob($this)->id, $fanart))->toBeFalse();
    });

    it('allows manageFanartists to authors only', function () {
        $author = alice($this);
        $story = publicStory('Histoire', $author->id);

        expect(fanartPolicy()->manageFanartists($author->id, $story->id))->toBeTrue()
            ->and(fanartPolicy()->manageFanartists(bob($this)->id, $story->id))->toBeFalse()
            ->and(fanartPolicy()->manageFanartists(moderator($this)->id, $story->id))->toBeFalse();
    });

    it('allows requestRole to a confirmed non-author non-fanartiste when a mode is designated', function () {
        $author = alice($this);
        $story = publicStory('Histoire', $author->id);
        $reader = bob($this);
        $fan = carol($this);
        makeFanartist($story->id, $fan->id);

        // defaults: images yes, audio designated
        expect(fanartPolicy()->requestRole($reader->id, $story->id))->toBeTrue()
            ->and(fanartPolicy()->requestRole($author->id, $story->id))->toBeFalse()
            ->and(fanartPolicy()->requestRole($fan->id, $story->id))->toBeFalse();

        setSettingsValue($author->id, 'stories', 'fanart-audio-mode', 'yes');
        expect(fanartPolicy()->requestRole($reader->id, $story->id))->toBeFalse();
    });
});
