<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestCategory;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestEntry;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestSettings;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestVote;
use App\Domains\Calendar\Private\Activities\QuoteContest\QuoteContestRegistration;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestPhaseService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Quote contest setup', function () {

    it('registers the quote-contest type in the calendar registry', function () {
        $registration = app(CalendarRegistry::class)->get(QuoteContestRegistration::ACTIVITY_TYPE);

        expect($registration)->toBeInstanceOf(QuoteContestRegistration::class)
            ->and($registration->displayComponentKey())->toBe('quote-contest::quote-contest-component')
            ->and($registration->configComponentKey())->toBe('quote-contest::quote-contest-config');
    });

    it('offers the quote-contest type in the admin dropdown with a French label', function () {
        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.create'))
            ->assertOk()
            ->assertSee('value="quote-contest"', false);

        expect(trans('calendar::activities.quote-contest', [], 'fr'))->toBe('Concours de citations');
    });

    it('renders the placeholder display component on the activity page', function () {
        // Guards the display component key: the sub-module answers to the same
        // `quote-contest::` prefix for a class component and an anonymous one,
        // and only the class must win here.
        $contest = createQuoteContest($this);

        $this->actingAs(alice($this))
            ->get($contest->url)
            ->assertOk()
            ->assertSee('quote-contest-activity');
    });

    it('lets an admin create a quote contest activity', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), [
                'name' => 'Concours de citations',
                'activity_type' => QuoteContestRegistration::ACTIVITY_TYPE,
                'active_starts_at' => '2026-09-01T10:00',
                'active_ends_at' => '2026-10-01T10:00',
                'quote_contest' => [
                    'submissions_end_at' => '2026-09-10T10:00',
                    'votes_start_at' => '2026-09-15T10:00',
                ],
            ])
            ->assertRedirect(route('calendar.admin.activities.index'));

        $this->assertDatabaseHas('calendar_activities', [
            'name' => 'Concours de citations',
            'activity_type' => 'quote-contest',
        ]);
    });

    it('deletes settings, categories, entries and votes when the activity is deleted', function () {
        $contest = createQuoteContest($this);

        $category = QuoteContestCategory::create([
            'activity_id' => $contest->id,
            'title' => 'La plus drôle',
            'description' => 'Les citations qui font rire.',
            'position' => 1,
        ]);

        $entry = QuoteContestEntry::create([
            'activity_id' => $contest->id,
            'category_id' => $category->id,
            'user_id' => 42,
            'quote_id' => 7,
            'story_id' => 3,
            'highlighted_text' => 'Un passage mémorable.',
            'story_title' => 'Mon histoire',
            'story_slug' => '3-mon-histoire',
            'chapter_id' => 11,
            'chapter_title' => 'Chapitre premier',
            'chapter_slug' => '11-chapitre-premier',
            'author_user_ids' => [42, 43],
        ]);

        QuoteContestVote::create([
            'category_id' => $category->id,
            'entry_id' => $entry->id,
            'user_id' => 44,
        ]);

        Activity::findOrFail($contest->id)->delete();

        expect(QuoteContestSettings::query()->count())->toBe(0)
            ->and(QuoteContestCategory::query()->count())->toBe(0)
            ->and(QuoteContestEntry::query()->count())->toBe(0)
            ->and(QuoteContestVote::query()->count())->toBe(0);
    });

    it('stores the entry author ids as a list', function () {
        $contest = createQuoteContest($this);

        $category = QuoteContestCategory::create([
            'activity_id' => $contest->id,
            'title' => 'La plus émouvante',
            'position' => 1,
        ]);

        $entry = QuoteContestEntry::create([
            'activity_id' => $contest->id,
            'category_id' => $category->id,
            'user_id' => 42,
            'quote_id' => 7,
            'story_id' => 3,
            'highlighted_text' => 'Un passage mémorable.',
            'story_title' => 'Mon histoire',
            'story_slug' => '3-mon-histoire',
            'chapter_id' => 11,
            'chapter_title' => 'Chapitre premier',
            'chapter_slug' => '11-chapitre-premier',
            'author_user_ids' => [42, 43],
        ]);

        expect($entry->fresh()->author_user_ids)->toBe([42, 43])
            ->and($entry->fresh()->withdrawn_at)->toBeNull()
            ->and($category->entries()->count())->toBe(1)
            ->and($entry->category->id)->toBe($category->id);
    });

    it('places each helper-built contest in the phase it names', function () {
        $service = app(QuoteContestPhaseService::class);

        $cases = [
            [createContestBeforeStart($this), QuoteContestPhase::BeforeStart],
            [createContestInSubmissions($this), QuoteContestPhase::Submissions],
            [createContestInInterlude($this), QuoteContestPhase::Interlude],
            [createContestInVoting($this), QuoteContestPhase::Voting],
            [createContestEnded($this), QuoteContestPhase::Ended],
        ];

        foreach ($cases as [$contest, $expected]) {
            expect($service->phaseFor($contest->activity, $contest->settings))->toBe($expected);
        }
    });
});
