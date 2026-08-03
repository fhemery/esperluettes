<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestSubmissionService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * A public story with one published chapter, ready to be quoted from.
 *
 * Returns an object: { story: Story, chapter: Chapter }
 */
function quotableStory(TestCase $t, Authenticatable $author, string $title, array $storyAttributes = []): object
{
    $story = publicStory($title, $author->id, $storyAttributes);

    return (object) [
        'story' => $story,
        'chapter' => createPublishedChapter($t, $story, $author, ['title' => 'Chapitre premier']),
    ];
}

describe('Mes citations — the picker', function () {

    it('lists every quote the reader owns', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = quotableStory($this, $author, 'Mon histoire');

        createQuote($reader->id, $source->chapter->id, $source->story->id, ['highlighted_text' => 'Le premier passage']);
        createQuote($reader->id, $source->chapter->id, $source->story->id, ['highlighted_text' => 'Le second passage']);

        $contest = createContestInSubmissions($this);
        makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader)
            ->get($contest->url)
            ->assertOk()
            ->assertSee('Le premier passage', false)
            ->assertSee('Le second passage', false)
            ->assertSee('Mon histoire', false)
            ->assertSee('Chapitre premier', false);
    });

    it('greys a quote whose story is private and says why', function () {
        $author = alice($this);
        $reader = bob($this);
        $secret = privateStory('Histoire secrète', $author->id);
        $secretChapter = createPublishedChapter($this, $secret, $author);

        createQuote($reader->id, $secretChapter->id, $secret->id, ['highlighted_text' => 'Passage confidentiel']);

        $contest = createContestInSubmissions($this);
        makeCategory($contest->id, 'La plus drôle');

        $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('Passage confidentiel')
            ->and($html)->toContain('quote-contest::quote-contest.ineligible.private_story')
            ->and($html)->toContain('aria-disabled="true"');
    });

    it('greys a quote whose story is excluded from events and says why', function () {
        $author = alice($this);
        $reader = bob($this);
        $excluded = quotableStory($this, $author, 'Histoire hors concours', ['is_excluded_from_events' => true]);

        createQuote($reader->id, $excluded->chapter->id, $excluded->story->id, ['highlighted_text' => 'Passage hors concours']);

        $contest = createContestInSubmissions($this);
        makeCategory($contest->id, 'La plus drôle');

        $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('Passage hors concours')
            ->and($html)->toContain('quote-contest::quote-contest.ineligible.excluded_from_events');
    });

    it('leaves an eligible quote ungreyed and unreasoned', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = quotableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id);

        $picker = app(QuoteContestSubmissionService::class)->pickerFor($reader->id);

        expect($picker)->toHaveCount(1)
            ->and($picker[0]->id)->toBe($quote->id)
            ->and($picker[0]->ineligibilityReason)->toBeNull()
            ->and($picker[0]->isEligible())->toBeTrue();
    });

    it('treats a community story as eligible', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = communityStory('Histoire communautaire', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id);

        $picker = app(QuoteContestSubmissionService::class)->pickerFor($reader->id);

        expect($picker[0]->ineligibilityReason)->toBeNull();
    });

    it('costs one batched story read whatever the quote count', function () {
        $author = alice($this);
        $small = bob($this);
        $large = carol($this);

        $sources = [];
        for ($s = 0; $s < 3; $s++) {
            $sources[] = quotableStory($this, $author, 'Histoire ' . $s);
        }

        foreach ($sources as $source) {
            createQuote($small->id, $source->chapter->id, $source->story->id);
            for ($q = 0; $q < 7; $q++) {
                createQuote($large->id, $source->chapter->id, $source->story->id);
            }
        }

        $service = app(QuoteContestSubmissionService::class);

        // Warm anything lazily resolved, so the comparison measures reads only.
        expect($service->pickerFor($small->id))->toHaveCount(3)
            ->and($service->pickerFor($large->id))->toHaveCount(21);

        $count = function (int $userId) use ($service): int {
            $queries = 0;
            DB::listen(function () use (&$queries) {
                $queries++;
            });
            $service->pickerFor($userId);

            return $queries;
        };

        $smallCount = $count($small->id);
        $largeCount = $count($large->id);

        expect($largeCount)->toBe($smallCount);
    });

    it('never shows another reader quotes', function () {
        $author = alice($this);
        $reader = bob($this);
        $other = carol($this);
        $source = quotableStory($this, $author, 'Mon histoire');

        createQuote($reader->id, $source->chapter->id, $source->story->id, ['highlighted_text' => 'Mon passage à moi']);
        createQuote($other->id, $source->chapter->id, $source->story->id, ['highlighted_text' => 'Le passage de quelqu un d autre']);

        $contest = createContestInSubmissions($this);
        makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader)
            ->get($contest->url)
            ->assertOk()
            ->assertSee('Mon passage à moi', false)
            ->assertDontSee('Le passage de quelqu un d autre', false);
    });

    it('never shows the private note of a quote', function () {
        // Assumption A1: the note never enters the contest, on any screen.
        $author = alice($this);
        $reader = bob($this);
        $source = quotableStory($this, $author, 'Mon histoire');
        createQuote($reader->id, $source->chapter->id, $source->story->id, [
            'note' => 'Ma note strictement personnelle',
        ]);

        $contest = createContestInSubmissions($this);
        makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader)
            ->get($contest->url)
            ->assertOk()
            ->assertDontSee('Ma note strictement personnelle', false);
    });

    it('shows an empty state to a reader with no quotes', function () {
        $contest = createContestInSubmissions($this);
        makeCategory($contest->id, 'La plus drôle');

        $this->actingAs(bob($this))
            ->get($contest->url)
            ->assertOk()
            ->assertSee('quote-contest::quote-contest.my_quotes.picker_empty', false);
    });

    it('says there is nothing to submit to when the contest has no category', function () {
        $contest = createContestInSubmissions($this);

        $this->actingAs(bob($this))
            ->get($contest->url)
            ->assertOk()
            ->assertSee('quote-contest::quote-contest.my_quotes.no_categories', false);
    });
});

describe('Mes citations — the phases', function () {

    it('says when submissions open and offers no picker before the start', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = quotableStory($this, $author, 'Mon histoire');
        createQuote($reader->id, $source->chapter->id, $source->story->id, ['highlighted_text' => 'Un passage à soumettre']);

        $contest = createContestBeforeStart($this);
        makeCategory($contest->id, 'La plus drôle');

        $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('quote-contest::quote-contest.phase.before_start')
            ->and($html)->toContain('La plus drôle')
            // No picker, therefore nothing to select and no filter box.
            ->and($html)->not->toContain('Un passage à soumettre')
            ->and($html)->not->toContain('quote-contest::quote-contest.my_quotes.filter_label');
    });

    it('renders the submission banner and the picker during the submission phase', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = quotableStory($this, $author, 'Mon histoire');
        createQuote($reader->id, $source->chapter->id, $source->story->id);

        $contest = createContestInSubmissions($this);
        makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader)
            ->get($contest->url)
            ->assertOk()
            ->assertSee('quote-contest::quote-contest.phase.submissions', false)
            ->assertSee('quote-contest::quote-contest.my_quotes.filter_label', false);
    });

    it('turns read-only during the interlude, entries still visible', function () {
        $reader = bob($this);
        $contest = createContestInInterlude($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, [
            'user_id' => $reader->id,
            'highlighted_text' => 'Ma citation en lice',
        ]);

        $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('quote-contest::quote-contest.phase.interlude')
            ->and($html)->toContain('Ma citation en lice')
            ->and($html)->not->toContain('quote-contest::quote-contest.my_quotes.filter_label');
    });

    it('stays read-only while voting and once ended', function () {
        $reader = bob($this);

        $voting = createContestInVoting($this);
        makeEntryIn(makeCategory($voting->id, 'La plus drôle'), [
            'user_id' => $reader->id,
            'highlighted_text' => 'Ma citation en lice',
        ]);

        $this->actingAs($reader)->get($voting->url)->assertOk()
            ->assertSee('quote-contest::quote-contest.phase.voting', false)
            ->assertSee('Ma citation en lice', false);

        $ended = createContestEnded($this);
        makeEntryIn(makeCategory($ended->id, 'La plus drôle'), [
            'user_id' => $reader->id,
            'highlighted_text' => 'Ma citation terminée',
        ]);

        $this->actingAs($reader)->get($ended->url)->assertOk()
            ->assertSee('quote-contest::quote-contest.phase.ended', false)
            ->assertSee('Ma citation terminée', false);
    });

    it('ignores a withdrawn entry when showing the reader current entry', function () {
        $reader = bob($this);
        $contest = createContestInInterlude($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, [
            'user_id' => $reader->id,
            'highlighted_text' => 'Citation retirée',
            'withdrawn_at' => now()->subHour(),
        ]);

        $entries = app(QuoteContestSubmissionService::class)
            ->currentEntriesFor($contest->id, $reader->id);

        expect($entries)->toBe([]);

        $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertDontSee('Citation retirée', false);
    });

    it('never carries a submitter id into a reader-facing view model', function () {
        // Architecture §3.3: anonymity is a query-shape guarantee. The reader's
        // own entry view model has no submitter field to leak.
        $reader = bob($this);
        $contest = createContestInInterlude($this);
        makeEntryIn(makeCategory($contest->id, 'La plus drôle'), ['user_id' => $reader->id]);

        $entry = array_values(
            app(QuoteContestSubmissionService::class)->currentEntriesFor($contest->id, $reader->id)
        )[0];

        $fields = array_keys(get_object_vars($entry));

        expect($fields)->not->toContain('userId')
            ->and($fields)->not->toContain('user_id')
            ->and($fields)->not->toContain('submitterId');
    });
});

describe('Mes citations — French wording', function () {

    it('words every reader-facing message in French', function () {
        $fr = fn (string $key) => trans('quote-contest::quote-contest.' . $key, [], 'fr');

        expect($fr('ineligible.private_story'))->toBe('Histoire privée')
            ->and($fr('ineligible.excluded_from_events'))->toBe('Histoire exclue des événements')
            ->and($fr('tab_my_quotes'))->toBe('Mes citations')
            ->and($fr('phase.ended'))->toBe('Le concours est terminé.');
    });
});
