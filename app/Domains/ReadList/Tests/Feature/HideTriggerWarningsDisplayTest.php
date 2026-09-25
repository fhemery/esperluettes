<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Public\Providers\StoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The gate lives entirely in Story's <x-story::trigger-warnings> component; no
 * ReadList code knows about the preference. These tests are what proves it.
 *
 * Each test sets the preferences *before* its only request, so the request-wide
 * memo of StoryPreferenceService never needs to be reset.
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
    ]);
    $this->noTw->tw_disclosure = Story::TW_NO_TW;
    $this->noTw->saveQuietly();
    createPublishedChapter($this, $this->noTw, $this->author);

    $this->actingAs($this->reader);
    addToReadList($this, $this->listed->id);
    addToReadList($this, $this->noTw->id);
});

describe('Read list and the hide-trigger-warnings preference', function () {
    it('hides trigger warning chrome on read-list cards when the preference is on', function () {
        setSettingsValue(
            $this->reader->id,
            StoryServiceProvider::TAB_STORIES,
            StoryServiceProvider::KEY_HIDE_TRIGGER_WARNINGS,
            true,
        );

        $resp = $this->get(route('readlist.index'));

        $resp->assertOk();
        $resp->assertSee('Listed TW');
        $resp->assertSee('Only No TW');
        $resp->assertDontSee('Violence');
        $resp->assertDontSee(__('story::shared.trigger_warnings.label'));
        $resp->assertDontSee(__('story::shared.trigger_warnings.tooltips.no_tw'));
    });

    it('keeps trigger warning chrome on read-list cards when the preference is off', function () {
        $resp = $this->get(route('readlist.index'));

        $resp->assertOk();
        $resp->assertSee('Violence');
        $resp->assertSee(__('story::shared.trigger_warnings.tooltips.no_tw'));
    });

    it('still lists trigger-warned stories on the read list', function () {
        // Decision #13: the pile is never filtered by hide-stories-with-tw.
        setSettingsValue(
            $this->reader->id,
            StoryServiceProvider::TAB_STORIES,
            StoryServiceProvider::KEY_HIDE_TRIGGER_WARNINGS,
            true,
        );
        setSettingsValue(
            $this->reader->id,
            StoryServiceProvider::TAB_STORIES,
            StoryServiceProvider::KEY_HIDE_STORIES_WITH_TW,
            true,
        );

        $resp = $this->get(route('readlist.index'));

        $resp->assertOk();
        $resp->assertSee('Listed TW');
        $resp->assertSee('Only No TW');
        $resp->assertDontSee('Violence');
    });
});
