<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Fanart\Private\Support\FanartViewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
    app()->setLocale('fr');
});

function tabUrl($story): string
{
    return route('fanart.tab', $story->slug);
}

function groupUrl($story, int|string $group): string
{
    return route('fanart.group', [$story->slug, $group]);
}

it('returns the tab with groups in chapter order plus Toute l\'œuvre', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $first = createPublishedChapter($this, $story, $author, ['title' => 'Premier chapitre']);
    $second = createPublishedChapter($this, $story, $author, ['title' => 'Second chapitre']);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $second->id]);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $first->id]);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => null]);

    $this->actingAs(bob($this))
        ->get(tabUrl($story))
        ->assertOk()
        ->assertSeeInOrder(['Premier chapitre', 'Second chapitre'])
        ->assertSee('data-group-key="work"', false)
        ->assertSee(__('fanart::ui.tab.whole_work'))
        ->assertSee(groupUrl($story, 'work'), false)
        ->assertSee(groupUrl($story, $first->id), false)
        ->assertSee(groupUrl($story, $second->id), false);
});

it('marks unread chapter groups collapsed with a spoiler notice for a reader, read ones expanded', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $read = createPublishedChapter($this, $story, $author, ['title' => 'Lu']);
    $unread = createPublishedChapter($this, $story, $author, ['title' => 'Pas lu']);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $read->id]);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $unread->id]);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => null]);

    $this->actingAs(bob($this));
    markAsRead($this, $read);

    $html = $this->get(tabUrl($story))->assertOk()->getContent();

    expect($html)
        ->toContain('data-group-key="' . $read->id . '" data-collapsed="false"')
        ->toContain('data-group-key="' . $unread->id . '" data-collapsed="true"')
        ->toContain('data-group-key="work" data-collapsed="false"')
        ->and(substr_count($html, e(__('fanart::ui.tab.spoiler'))))->toBe(1);
});

it('expands every group for an author and shows the pending block', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapitre']);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $chapter->id]);
    createFanart([
        'story_id' => $story->id,
        'user_id' => bob($this)->id,
        'status' => FanartStatus::Pending,
        'published_at' => null,
        'title' => 'Proposition en attente',
    ]);

    $html = $this->actingAs($author)->get(tabUrl($story))->assertOk()->getContent();

    expect($html)
        ->toContain('data-group-key="' . $chapter->id . '" data-collapsed="false"')
        ->toContain(e(__('fanart::ui.tab.pending_title')))
        ->toContain('Proposition en attente')
        ->not->toContain(e(__('fanart::ui.tab.spoiler')));
});

it('explains the effective mode to authors, including the co-author rule', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    setSettingsValue($author->id, 'stories', 'fanart-images-mode', 'yes');

    $this->actingAs($author)->get(tabUrl($story))->assertOk()
        ->assertSee(__('fanart::ui.tab.mode_images_yes'))
        ->assertSee(__('fanart::ui.tab.mode_audio_designated'))
        ->assertDontSee(__('fanart::ui.tab.mode_coauthors'));

    $coauthored = publicStory('Duo', $author->id);
    addCollaborator($coauthored->id, bob($this)->id);

    $this->actingAs($author)->get(tabUrl($coauthored))->assertOk()
        ->assertSee(__('fanart::ui.tab.mode_coauthors'));

    $this->actingAs(carol($this))->get(tabUrl($story))->assertOk()
        ->assertDontSee(__('fanart::ui.tab.mode_images_yes'));
});

it('never lists pending fanarts to a reader, lists them to the submitter only in their own context', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $submitter = bob($this);
    $mine = createFanart([
        'story_id' => $story->id,
        'user_id' => $submitter->id,
        'status' => FanartStatus::Pending,
        'published_at' => null,
        'title' => 'Mon en attente',
    ]);
    $other = createFanart([
        'story_id' => $story->id,
        'user_id' => carol($this)->id,
        'status' => FanartStatus::Pending,
        'published_at' => null,
        'title' => 'Celui des autres',
    ]);

    // On the story tab the submitter is just a reader.
    $this->actingAs($submitter)->get(tabUrl($story))->assertOk()
        ->assertDontSee('Mon en attente')->assertDontSee('Celui des autres');
    $this->get(groupUrl($story, 'work'))->assertOk()
        ->assertDontSee('Mon en attente')->assertDontSee('Celui des autres');

    // The submitter's own context (profile tab) opts in to their own pending items.
    $ownContext = new FanartViewer(userId: $submitter->id, seesAll: false, publishedChapterIds: [], includeOwnPending: true);
    expect(Fanart::visibleTo($ownContext)->pluck('id')->all())->toBe([$mine->id]);

    $readerContext = new FanartViewer(userId: $submitter->id, seesAll: false, publishedChapterIds: []);
    expect(Fanart::visibleTo($readerContext)->count())->toBe(0);
    expect($other->id)->not->toBe($mine->id);
});

it('omits groups of unpublished chapters for readers, keeps them for authors', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $draft = createUnpublishedChapter($this, $story, $author, ['title' => 'Brouillon secret']);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $draft->id, 'title' => 'Caché']);

    $this->actingAs(bob($this))->get(tabUrl($story))->assertOk()
        ->assertDontSee('Brouillon secret')
        ->assertDontSee(groupUrl($story, $draft->id), false);
    $this->get(groupUrl($story, $draft->id))->assertNotFound();

    $this->actingAs($author)->get(tabUrl($story))->assertOk()
        ->assertSee('Brouillon secret')
        ->assertSee(groupUrl($story, $draft->id), false);
    $this->get(groupUrl($story, $draft->id))->assertOk()->assertSee('Caché');
});

it('renders the empty state when nothing is published', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    createFanart([
        'story_id' => $story->id,
        'user_id' => $author->id,
        'status' => FanartStatus::Pending,
        'published_at' => null,
    ]);

    $this->actingAs(bob($this))->get(tabUrl($story))->assertOk()
        ->assertSee(__('fanart::ui.tab.empty'));
});

it('renders a group with thumbnails only, without a spoiler for the group endpoint itself', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $image = createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $chapter->id, 'title' => 'Portrait']);
    $audio = createFanart([
        'story_id' => $story->id,
        'user_id' => $author->id,
        'chapter_id' => $chapter->id,
        'kind' => FanartKind::Audio,
        'title' => 'Lecture',
    ]);

    $this->actingAs(bob($this))->get(groupUrl($story, $chapter->id))->assertOk()
        ->assertSee(route('fanart.thumb', [$image->id, 400]), false)
        ->assertSee(route('fanart.thumb', [$image->id, 800]), false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('alt="Portrait"', false)
        ->assertDontSee(route('fanart.file', $audio->id), false);
});

it('shows a report flag on a miniature of another user\'s fanart', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $chapter->id]);

    $this->actingAs(bob($this))->get(groupUrl($story, $chapter->id))->assertOk()
        ->assertSee("reportButton('fanart', {$fanart->id})", false);
});

it('hides the report flag on the viewer\'s own fanart miniature', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $chapter->id]);

    $this->actingAs($author)->get(groupUrl($story, $chapter->id))->assertOk()
        ->assertDontSee('reportButton', false);
});

it('renders the viewer with title, linked artist, sanitised description, report and delete for the submitter', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $artist = bob($this);
    $fanart = createFanart([
        'story_id' => $story->id,
        'user_id' => $artist->id,
        'title' => 'Portrait',
        'description' => '<p>Belle <strong>image</strong></p>',
    ]);

    $deleteForm = 'action="' . route('fanart.destroy', $fanart->id) . '"';

    $this->actingAs(carol($this))->get(route('fanart.show', $fanart->id))->assertOk()
        ->assertSee('Portrait')
        ->assertSee('<strong>image</strong>', false)
        ->assertSee('>Bob</a>', false)
        ->assertSee(route('profile.show', ['profile' => app(\App\Domains\Shared\Contracts\ProfilePublicApi::class)->getPublicProfile($artist->id)->slug]), false)
        ->assertSee(route('fanart.file', $fanart->id), false)
        ->assertSee('reportButton', false)
        ->assertDontSee($deleteForm, false);

    $this->actingAs($artist)->get(route('fanart.show', $fanart->id))->assertOk()
        ->assertSee($deleteForm, false)
        ->assertDontSee('reportButton', false);

    $this->actingAs($author)->get(route('fanart.show', $fanart->id))->assertOk()
        ->assertSee($deleteForm, false);
});

it('renders the sound viewer with a download-protected player', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $audio = createFanart(['story_id' => $story->id, 'user_id' => bob($this)->id, 'kind' => FanartKind::Audio, 'file_path' => 'fanart/1/a.mp3']);

    $this->actingAs(carol($this))->get(route('fanart.show', $audio->id))->assertOk()
        ->assertSee('controlsList="nodownload', false)
        ->assertSee('oncontextmenu', false);
});

it('renders the review viewer with comment + Accepter/Décliner for an author on a pending fanart', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $pending = createFanart([
        'story_id' => $story->id,
        'user_id' => bob($this)->id,
        'status' => FanartStatus::Pending,
        'published_at' => null,
    ]);

    $this->actingAs($author)->get(route('fanart.show', $pending->id))->assertOk()
        ->assertSee('name="comment"', false)
        ->assertSee(route('fanart.accept', $pending->id), false)
        ->assertSee(route('fanart.decline', $pending->id), false)
        ->assertSee('reportButton', false);

    // The submitter sees their own pending item, but cannot review it.
    $this->actingAs(bob($this))->get(route('fanart.show', $pending->id))->assertOk()
        ->assertDontSee('name="comment"', false)
        ->assertDontSee(route('fanart.accept', $pending->id), false);

    // Any other reader is refused.
    $this->actingAs(carol($this))->get(route('fanart.show', $pending->id))->assertForbidden();
});

it('refuses the viewer for a fanart of an unpublished chapter to a reader', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $draft = createUnpublishedChapter($this, $story, $author);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $draft->id]);

    $this->actingAs(bob($this))->get(route('fanart.show', $fanart->id))->assertForbidden();
    $this->actingAs($author)->get(route('fanart.show', $fanart->id))->assertOk();
});

it('renders the moderation menu only for staff', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => bob($this)->id]);
    $url = route('fanart.moderation.destroy', $fanart->id);

    $this->actingAs(moderator($this))->get(route('fanart.show', $fanart->id))->assertOk()
        ->assertSee($url, false);

    foreach ([$author, bob($this), carol($this)] as $user) {
        $this->actingAs($user)->get(route('fanart.show', $fanart->id))->assertOk()
            ->assertDontSee($url, false);
    }
});

it('turns every fragment away from a non-confirmed user and a guest, and 403s a private story', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $author->id]);
    $urls = [tabUrl($story), groupUrl($story, 'work'), route('fanart.show', $fanart->id)];

    foreach ($urls as $url) {
        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));
    }

    $this->actingAs(bob($this, roles: [Roles::USER]));
    foreach ($urls as $url) {
        // The convention `role` middleware redirects a non-confirmed user, never serves a body.
        $this->get($url)->assertRedirect();
    }

    $private = privateStory('Secrète', $author->id);
    $hidden = createFanart(['story_id' => $private->id, 'user_id' => $author->id]);
    $this->actingAs(carol($this));
    $this->get(tabUrl($private))->assertForbidden();
    $this->get(groupUrl($private, 'work'))->assertForbidden();
    $this->get(route('fanart.show', $hidden->id))->assertForbidden();
});

it('loads a group without N+1 (query count bound)', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    $artists = [bob($this), carol($this)];
    $make = function (int $n) use ($story, $chapter, $artists) {
        for ($i = 0; $i < $n; $i++) {
            createFanart([
                'story_id' => $story->id,
                'chapter_id' => $chapter->id,
                'user_id' => $artists[intdiv($i, 2) % 2]->id,
                'kind' => $i % 2 === 0 ? FanartKind::Audio : FanartKind::Image,
            ]);
        }
    };
    $count = function () use ($story, $chapter): int {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });
        $this->get(groupUrl($story, $chapter->id))->assertOk();

        return $queries;
    };

    $make(4);
    $this->actingAs($artists[0]);
    $this->get(groupUrl($story, $chapter->id))->assertOk(); // warm caches
    $small = $count();

    $make(12);
    $large = $count();

    expect($large)->toBe($small);
});

it('leaves published sounds out of the tab summary, keeping them pending-only', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($this, $story, $author);
    createFanart(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => $chapter->id, 'kind' => FanartKind::Audio]);

    $this->actingAs(bob($this))->get(tabUrl($story))->assertOk()
        ->assertDontSee('data-group-key', false)
        ->assertSee(__('fanart::ui.tab.empty'));
});
