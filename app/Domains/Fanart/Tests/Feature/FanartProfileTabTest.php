<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
    app()->setLocale('fr');
});

const PROFILE_TAB_MARKER = 'data-fanart-profile-tab';

function profileTabUrl($owner): string
{
    $slug = app(ProfilePublicApi::class)->getPublicProfile($owner->id)->slug;

    return route('profile.show.tab', [$slug, 'fanarts']);
}

function profileGroupUrl($owner, $story, string $kind = 'image'): string
{
    return route('fanart.profile.group', [$owner->id, $story->id]) . '?kind=' . $kind;
}

function hideFanartTab($owner): void
{
    app(SettingsPublicApi::class)->setValue($owner->id, 'profile', 'hide-fanart-tab', true);
}

it('hides the tab from everyone but owner and staff once the owner hides it', function () {
    $owner = bob($this);
    $story = publicStory('Histoire', alice($this)->id);
    createFanart(['story_id' => $story->id, 'user_id' => $owner->id]);
    hideFanartTab($owner);

    $this->actingAs($owner)->get(profileTabUrl($owner))->assertOk()->assertSee(PROFILE_TAB_MARKER, false);
    $this->actingAs(moderator($this))->get(profileTabUrl($owner))->assertOk()->assertSee(PROFILE_TAB_MARKER, false);

    $viewer = carol($this);
    $this->actingAs($viewer)->get(profileTabUrl($owner))->assertRedirect();
    $this->get(profileGroupUrl($owner, $story))->assertForbidden();
});

it('shows the tab to confirmed viewers by default, never to guests or non-confirmed', function () {
    $owner = bob($this);
    $story = publicStory('Histoire', alice($this)->id);
    createFanart(['story_id' => $story->id, 'user_id' => $owner->id]);

    $this->actingAs(carol($this))->get(profileTabUrl($owner))->assertOk()->assertSee(PROFILE_TAB_MARKER, false);
    $this->get(profileGroupUrl($owner, $story))->assertOk();

    $unconfirmed = daniel($this, [], true, [Roles::USER]);
    $this->actingAs($unconfirmed)->get(profileTabUrl($owner))->assertRedirect();
    $this->get(profileGroupUrl($owner, $story))->assertStatus(302);

    auth()->logout();
    $this->get(profileTabUrl($owner))->assertRedirect();
    $this->get(profileGroupUrl($owner, $story))->assertRedirect();
});

it('lists only fanarts on stories the viewer can read', function () {
    $author = alice($this);
    $owner = bob($this);
    $public = publicStory('Histoire publique', $author->id);
    $private = privateStory('Histoire secrète', $author->id);
    createFanart(['story_id' => $public->id, 'user_id' => $owner->id, 'title' => 'Visible']);
    createFanart(['story_id' => $private->id, 'user_id' => $owner->id, 'title' => 'Cachée']);

    $viewer = carol($this);
    $this->actingAs($viewer)->get(profileTabUrl($owner))->assertOk()
        ->assertSee('Histoire publique')
        ->assertDontSee('Histoire secrète');
    $this->get(profileGroupUrl($owner, $public))->assertOk()->assertSee('Visible', false);
    $this->get(profileGroupUrl($owner, $private))->assertForbidden();

    // The author of the private story does read it.
    $this->actingAs($author)->get(profileGroupUrl($owner, $private))->assertOk()->assertSee('Cachée', false);
});

it('splits images and sounds and hides chapters that are not published from readers', function () {
    $author = alice($this);
    $owner = bob($this);
    $story = publicStory('Histoire', $author->id);
    $published = createPublishedChapter($this, $story, $author, ['title' => 'Chapitre publié']);
    $draft = createUnpublishedChapter($this, $story, $author, ['title' => 'Brouillon']);
    createFanart(['story_id' => $story->id, 'user_id' => $owner->id, 'chapter_id' => $published->id, 'title' => 'Image du chapitre']);
    createFanart(['story_id' => $story->id, 'user_id' => $owner->id, 'chapter_id' => $draft->id, 'title' => 'Image du brouillon']);
    createFanart(['story_id' => $story->id, 'user_id' => $owner->id, 'kind' => FanartKind::Audio, 'chapter_id' => $published->id, 'title' => 'Lecture du chapitre']);

    $this->actingAs(carol($this))->get(profileGroupUrl($owner, $story, 'image'))->assertOk()
        ->assertSee('Image du chapitre', false)
        ->assertDontSee('Image du brouillon', false)
        ->assertDontSee('Lecture du chapitre', false);
    $this->get(profileGroupUrl($owner, $story, 'audio'))->assertOk()
        ->assertSee('Lecture du chapitre', false)
        ->assertSee('controlsList="nodownload', false)
        ->assertSee('oncontextmenu', false)
        ->assertDontSee('Image du chapitre', false);
    $this->get(profileGroupUrl($owner, $story, 'bogus'))->assertNotFound();
});

it('shows pending items with a badge to the owner only', function () {
    $owner = bob($this);
    $story = publicStory('Zorglub', alice($this)->id);
    createFanart([
        'story_id' => $story->id, 'user_id' => $owner->id, 'title' => 'En cours',
        'status' => FanartStatus::Pending, 'published_at' => null,
    ]);

    $this->actingAs($owner)->get(profileGroupUrl($owner, $story))->assertOk()
        ->assertSee('En cours', false)
        ->assertSee('En attente de validation');

    $this->actingAs(carol($this))->get(profileGroupUrl($owner, $story))->assertOk()
        ->assertDontSee('En cours', false)
        ->assertDontSee('En attente de validation');
    $this->get(profileTabUrl($owner))->assertOk()->assertDontSee('Zorglub');

    // A7: staff see pending items.
    $this->actingAs(moderator($this))->get(profileGroupUrl($owner, $story))->assertOk()
        ->assertSee('En cours', false)
        ->assertSee('En attente de validation');
});

it('collapses groups of chapters the viewer has not read', function () {
    $author = alice($this);
    $owner = bob($this);
    $story = publicStory('Histoire', $author->id);
    $read = createPublishedChapter($this, $story, $author, ['title' => 'Lu']);
    $unread = createPublishedChapter($this, $story, $author, ['title' => 'Pas lu']);
    foreach ([$read, $unread, null] as $chapter) {
        createFanart(['story_id' => $story->id, 'user_id' => $owner->id, 'chapter_id' => $chapter?->id]);
    }

    $this->actingAs(carol($this));
    markAsRead($this, $read);

    $html = $this->get(profileGroupUrl($owner, $story))->assertOk()->getContent();
    expect($html)
        ->toContain('data-group-key="' . $read->id . '" data-collapsed="false"')
        ->toContain('data-group-key="' . $unread->id . '" data-collapsed="true"')
        ->toContain('data-group-key="work" data-collapsed="false"')
        ->and(substr_count($html, e(__('fanart::ui.tab.spoiler'))))->toBe(1);

    // Spoiler collapsing never applies to an author of the story.
    $html = $this->actingAs($author)->get(profileGroupUrl($owner, $story))->assertOk()->getContent();
    expect($html)->not->toContain('data-collapsed="true"');
});

it('lets the owner delete from the tab', function () {
    $owner = bob($this);
    $story = publicStory('Histoire', alice($this)->id);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $owner->id]);
    $deleteAction = route('fanart.destroy', $fanart->id);

    // The viewer URL shares the delete path (other verb): the form itself is what must be absent.
    $deleteForm = 'name="_method" value="DELETE"';
    $this->actingAs(carol($this))->get(profileGroupUrl($owner, $story))->assertOk()
        ->assertDontSee($deleteForm, false);
    $this->actingAs(moderator($this))->get(profileGroupUrl($owner, $story))->assertOk()
        ->assertDontSee($deleteForm, false);

    $this->actingAs($owner)->get(profileGroupUrl($owner, $story))->assertOk()
        ->assertSee($deleteForm, false)
        ->assertSee('action="' . $deleteAction . '"', false);
    $this->delete($deleteAction)->assertRedirect()->assertSessionHas('success');

    expect(Fanart::withTrashed()->find($fanart->id))->toBeNull();
});

it('uses a single readability query for many stories', function () {
    $author = alice($this);
    $owner = bob($this);
    foreach (range(1, 4) as $i) {
        $story = publicStory("Histoire $i", $author->id);
        createFanart(['story_id' => $story->id, 'user_id' => $owner->id]);
    }

    // A proxied partial: calls fall through to the real API, but are counted.
    $real = app(StoryPublicApi::class);
    $spy = Mockery::mock($real);
    $spy->shouldReceive('filterReadableStoryIds')->once()
        ->andReturnUsing(fn (...$args) => $real->filterReadableStoryIds(...$args));
    $spy->shouldNotReceive('canRead');
    $spy->shouldNotReceive('isAuthor');
    app()->instance(StoryPublicApi::class, $spy);

    $this->actingAs(carol($this))->get(profileTabUrl($owner))->assertOk()
        ->assertSee('Histoire 1')->assertSee('Histoire 4');
});
