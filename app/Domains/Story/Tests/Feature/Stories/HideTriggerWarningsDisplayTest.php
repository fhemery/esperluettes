<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\StoryPreferenceService;
use App\Domains\Story\Public\Providers\StoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * StoryPreferenceService is a container singleton memoized for the whole
 * request, and the container is NOT rebuilt between two HTTP calls inside one
 * test — hence the `forgetInstance` right after every `setSettingsValue`.
 */

beforeEach(function () {
    Cache::flush();

    $this->author = alice($this);
    $this->reader = bob($this);

    $violence = makeRefTriggerWarning('Violence');

    $this->listed = publicStory('Listed TW', $this->author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [$violence->id],
    ]);
    $this->listed->tw_disclosure = Story::TW_LISTED;
    $this->listed->saveQuietly();
    createPublishedChapter($this, $this->listed, $this->author);

    $this->noTw = publicStory('Only No TW', $this->author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [],
    ]);
    $this->noTw->tw_disclosure = Story::TW_NO_TW;
    $this->noTw->saveQuietly();
    createPublishedChapter($this, $this->noTw, $this->author);

    $this->unspoiled = publicStory('Unspoiled Tale', $this->author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [],
    ]);
    $this->unspoiled->tw_disclosure = Story::TW_UNSPOILED;
    $this->unspoiled->saveQuietly();
    createPublishedChapter($this, $this->unspoiled, $this->author);
});

function hideTriggerWarningsFor(int $userId): void
{
    setSettingsValue(
        $userId,
        StoryServiceProvider::TAB_STORIES,
        StoryServiceProvider::KEY_HIDE_TRIGGER_WARNINGS,
        true,
    );
    app()->forgetInstance(StoryPreferenceService::class);
}

describe('Story cards and the hide-trigger-warnings preference', function () {
    it('hides trigger warning names on story cards when the preference is on', function () {
        hideTriggerWarningsFor($this->reader->id);

        $resp = $this->actingAs($this->reader)->get('/stories');

        $resp->assertOk();
        $resp->assertSee('Listed TW');
        $resp->assertDontSee(__('story::shared.trigger_warnings.label'));
        // The card renders no TW name at all. The single remaining occurrence of
        // « Violence » on the page is the deliberately ungated `exclude_tw`
        // filter panel (assumption #6), which serialises every *reference* TW
        // name into its Alpine x-data and discloses no individual story's.
        expect(substr_count($resp->getContent(), 'Violence'))->toBe(1);
    });

    it('hides the no_tw and unspoiled markers on story cards', function () {
        hideTriggerWarningsFor($this->reader->id);

        $resp = $this->actingAs($this->reader)->get('/stories');

        $resp->assertOk();
        $resp->assertSee('Only No TW');
        $resp->assertSee('Unspoiled Tale');
        $resp->assertDontSee(__('story::shared.trigger_warnings.tooltips.no_tw'));
        $resp->assertDontSee(__('story::shared.trigger_warnings.tooltips.unspoiled'));
        $resp->assertDontSee(__('story::shared.trigger_warnings.tooltips.listed'));
    });

    it('keeps trigger warnings on story cards when the preference is off', function () {
        $resp = $this->actingAs($this->reader)->get('/stories');

        $resp->assertOk();
        // Once in the filter panel, once on the card of « Listed TW ».
        expect(substr_count($resp->getContent(), 'Violence'))->toBe(2);
        $resp->assertSee(__('story::shared.trigger_warnings.tooltips.no_tw'));
        $resp->assertSee(__('story::shared.trigger_warnings.tooltips.unspoiled'));
    });

    it('keeps trigger warnings on story cards for a guest', function () {
        $resp = $this->get('/stories');

        $resp->assertOk();
        expect(substr_count($resp->getContent(), 'Violence'))->toBe(2);
        $resp->assertSee(__('story::shared.trigger_warnings.tooltips.no_tw'));
        $resp->assertSee(__('story::shared.trigger_warnings.tooltips.unspoiled'));
    });
});

describe('Story page and the hide-trigger-warnings preference', function () {
    it('hides the trigger warnings block on the story page', function () {
        hideTriggerWarningsFor($this->reader->id);
        $this->actingAs($this->reader);

        $listed = $this->get('/stories/' . $this->listed->slug);
        $listed->assertOk();
        $listed->assertSee('Listed TW');
        $listed->assertDontSee(__('story::show.trigger_warnings.label'));
        $listed->assertDontSee('Violence');

        $noTw = $this->get('/stories/' . $this->noTw->slug);
        $noTw->assertOk();
        $noTw->assertDontSee(__('story::show.trigger_warnings.label'));
        $noTw->assertDontSee(__('story::shared.trigger_warnings.no_tw'));

        $unspoiled = $this->get('/stories/' . $this->unspoiled->slug);
        $unspoiled->assertOk();
        $unspoiled->assertDontSee(__('story::show.trigger_warnings.label'));
        $unspoiled->assertDontSee(__('story::shared.trigger_warnings.unspoiled'));
    });

    it('keeps the trigger warnings block on the story page when the preference is off', function () {
        $this->actingAs($this->reader);

        $listed = $this->get('/stories/' . $this->listed->slug);
        $listed->assertOk();
        $listed->assertSee(__('story::show.trigger_warnings.label'));
        $listed->assertSee('Violence');

        $noTw = $this->get('/stories/' . $this->noTw->slug);
        $noTw->assertOk();
        $noTw->assertSee(__('story::show.trigger_warnings.label'));
        $noTw->assertSee(__('story::shared.trigger_warnings.no_tw'));

        $unspoiled = $this->get('/stories/' . $this->unspoiled->slug);
        $unspoiled->assertOk();
        $unspoiled->assertSee(__('story::show.trigger_warnings.label'));
        $unspoiled->assertSee(__('story::shared.trigger_warnings.unspoiled'));
    });

    it('keeps the trigger warnings block on the story page for a guest', function () {
        $resp = $this->get('/stories/' . $this->listed->slug);

        $resp->assertOk();
        $resp->assertSee(__('story::show.trigger_warnings.label'));
        $resp->assertSee('Violence');
    });
});

describe('Surfaces the hide-trigger-warnings preference must not touch', function () {
    it('keeps the trigger warning picker on the author edit form', function () {
        // Assumption #5: configuration UIs stay ungated.
        hideTriggerWarningsFor($this->author->id);

        $resp = $this->actingAs($this->author)->get('/stories/' . $this->listed->slug . '/edit');

        $resp->assertOk();
        $resp->assertSee(__('story::shared.trigger_warnings.label'));
        $resp->assertSee('Violence');
    });

    it('keeps the library trigger-warning filter panel', function () {
        // Assumption #6: a filtering control reveals no individual story's warnings.
        hideTriggerWarningsFor($this->reader->id);

        $resp = $this->actingAs($this->reader)->get('/stories');

        $resp->assertOk();
        $resp->assertSee(__('story::index.filters.no_tw_only.label'));
        $resp->assertSee(__('story::index.filters.trigger_warnings.label'));
    });
});
