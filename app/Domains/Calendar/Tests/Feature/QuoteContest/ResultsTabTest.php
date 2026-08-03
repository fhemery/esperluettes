<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestVote;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestVoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** @return array<int, \App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\ResultsCategoryViewModel> */
function contestResultsFor(object $contest): array
{
    return app(QuoteContestVoteService::class)->resultsFor($contest->id);
}

/** A ballot cast straight into the table, so a tally can be set up in any phase. */
function castRawVote(int $categoryId, int $entryId, int $userId): void
{
    QuoteContestVote::create([
        'category_id' => $categoryId,
        'entry_id' => $entryId,
        'user_id' => $userId,
    ]);
}

describe('The Résultats tab — who reaches it', function () {

    it('shows a moderator every entry with its vote count and its submitter', function () {
        $submitter = carol($this, ['name' => 'Soumettrice Connue', 'email' => 'soumettrice@example.com']);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, [
            'user_id' => $submitter->id,
            'highlighted_text' => 'Un passage en lice',
        ]);

        castRawVote((int) $category->id, (int) $entry->id, bob($this)->id);
        castRawVote((int) $category->id, (int) $entry->id, daniel($this)->id);

        $this->actingAs(moderator($this))->get($contest->url)->assertOk()
            ->assertSee('quote-contest::quote-contest.tab_results', false)
            ->assertSee('Un passage en lice', false)
            ->assertSee('Soumettrice Connue', false);

        $results = contestResultsFor($contest);

        expect($results[0]->entries[0]->voteCount)->toBe(2)
            ->and($results[0]->entries[0]->submitterName)->toBe('Soumettrice Connue');
    });

    it('shows an admin the results tab', function () {
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, ['user_id' => carol($this)->id, 'highlighted_text' => 'Un passage en lice']);

        $this->actingAs(admin($this))->get($contest->url)->assertOk()
            ->assertSee('quote-contest::quote-contest.tab_results', false)
            ->assertSee('Un passage en lice', false);
    });

    it('shows a tech admin the results tab', function () {
        // Open item O2: tech-admin is admin everywhere else in the codebase,
        // and every moderation surface here reads the same three roles.
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, ['user_id' => carol($this)->id, 'highlighted_text' => 'Un passage en lice']);

        $this->actingAs(techAdmin($this))->get($contest->url)->assertOk()
            ->assertSee('quote-contest::quote-contest.tab_results', false);
    });

    it('offers a confirmed user neither the tab nor any submitter identity', function () {
        // Architecture §3.3, point 4: the tab is *absent* from the array, never
        // rendered and then hidden — and §3.3's query-shape guarantee means the
        // submitter is nowhere in the objects the reader-facing tabs receive.
        $submitter = carol($this, ['name' => 'Soumettrice Connue', 'email' => 'soumettrice@example.com']);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, [
            'user_id' => $submitter->id,
            'highlighted_text' => 'Un passage en lice',
        ]);

        $html = $this->actingAs(bob($this))->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('Un passage en lice')
            ->and($html)->not->toContain('quote-contest::quote-contest.tab_results')
            ->and($html)->not->toContain('Soumettrice Connue')
            ->and($html)->not->toContain('quote-contest::quote-contest.results.vote_count');
    });

    it('keeps the results tab available before, during and after the contest', function () {
        $submitter = carol($this);
        $moderator = moderator($this);

        $contests = [
            createContestBeforeStart($this),
            createContestInSubmissions($this, ['name' => 'Concours en soumissions']),
            createContestInInterlude($this, ['name' => 'Concours en entre-deux']),
            createContestInVoting($this, ['name' => 'Concours en votes']),
            createContestEnded($this, ['name' => 'Concours terminé']),
        ];

        foreach ($contests as $index => $contest) {
            $category = makeCategory($contest->id, 'La plus drôle');
            makeEntryIn($category, [
                'user_id' => $submitter->id,
                'highlighted_text' => 'Passage de phase ' . $index,
            ]);

            $this->actingAs($moderator)->get($contest->url)->assertOk()
                ->assertSee('quote-contest::quote-contest.tab_results', false)
                ->assertSee('Passage de phase ' . $index, false);
        }
    });
});

describe('The Résultats tab — what it counts', function () {

    it('leaves withdrawn entries out of the vote counts but still shows them', function () {
        // Tradeoff 3: the votes rows stay, but they stop counting — and
        // *Résultats* can still show what happened.
        $submitter = carol($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $live = makeEntryIn($category, ['user_id' => $submitter->id, 'highlighted_text' => 'La citation en lice']);
        $withdrawn = makeEntryIn($category, [
            'user_id' => daniel($this)->id,
            'highlighted_text' => 'La citation retirée',
            'withdrawn_at' => now()->subHour(),
        ]);

        castRawVote((int) $category->id, (int) $live->id, bob($this)->id);
        castRawVote((int) $category->id, (int) $withdrawn->id, alice($this)->id);

        $this->actingAs(moderator($this))->get($contest->url)->assertOk()
            ->assertSee('La citation en lice', false)
            ->assertSee('La citation retirée', false)
            ->assertSee('quote-contest::quote-contest.results.withdrawn', false);

        $entries = collect(contestResultsFor($contest)[0]->entries)->keyBy('id');

        expect($entries[(int) $live->id]->voteCount)->toBe(1)
            ->and($entries[(int) $live->id]->isWithdrawn)->toBeFalse()
            ->and($entries[(int) $withdrawn->id]->voteCount)->toBe(0)
            ->and($entries[(int) $withdrawn->id]->isWithdrawn)->toBeTrue()
            // Decision #18: the row is still in the table, only uncounted.
            ->and(QuoteContestVote::query()->where('entry_id', $withdrawn->id)->count())->toBe(1);
    });

    it('counts nothing for an entry nobody voted for', function () {
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, ['user_id' => carol($this)->id]);

        expect(contestResultsFor($contest)[0]->entries[0]->voteCount)->toBe(0);
    });

    it('still shows an entry whose submitter no longer exists', function () {
        // Spec §5 / decision #7: the entry stays, with no identifiable submitter.
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, [
            'user_id' => 999999,
            'highlighted_text' => 'Un passage orphelin',
        ]);

        $this->actingAs(moderator($this))->get($contest->url)->assertOk()
            ->assertSee('Un passage orphelin', false)
            ->assertSee('quote-contest::quote-contest.results.unknown_submitter', false);

        $entry = contestResultsFor($contest)[0]->entries[0];

        expect($entry->submitterName)->toBeNull()
            ->and($entry->submitterUrl)->toBeNull();
    });

    it('shows the passage, the links and the story authors', function () {
        $author = alice($this, ['name' => 'Autrice En Lice', 'email' => 'autrice@example.com']);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, [
            'user_id' => carol($this)->id,
            'highlighted_text' => 'Un passage en lice',
            'story_title' => 'Une histoire en lice',
            'story_slug' => '3-une-histoire-en-lice',
            'chapter_title' => 'Un chapitre en lice',
            'chapter_slug' => '11-un-chapitre-en-lice',
            'author_user_ids' => [$author->id],
        ]);

        $this->actingAs(moderator($this))->get($contest->url)->assertOk()
            ->assertSee('Une histoire en lice', false)
            ->assertSee('Un chapitre en lice', false)
            ->assertSee(route('stories.show', ['slug' => '3-une-histoire-en-lice']), false)
            ->assertSee('Autrice En Lice', false);
    });

    it('never prints the note, which no entry ever stored', function () {
        // Assumption A1: the reader's private note never enters the contest.
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Mon histoire', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapitre premier']);
        $quote = createQuote($reader->id, $chapter->id, $story->id, [
            'highlighted_text' => 'Un passage à soumettre',
            'note' => 'Ma note strictement privée',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader)
            ->post(route('quote-contest.entries.store', $contest->id), [
                'category_id' => $category->id,
                'quote_id' => $quote->id,
            ])->assertRedirect();

        $this->actingAs(moderator($this))->get($contest->url)->assertOk()
            ->assertSee('Un passage à soumettre', false)
            ->assertDontSee('Ma note strictement privée', false);
    });
});

describe('Résultats — French wording', function () {

    it('words every moderation label in French', function () {
        $fr = fn (string $key) => trans('quote-contest::quote-contest.results.' . $key, [], 'fr');

        expect(trans('quote-contest::quote-contest.tab_results', [], 'fr'))->toBe('Résultats')
            ->and($fr('vote_count'))->toBe('Votes')
            ->and($fr('submitter'))->toBe('Soumise par')
            ->and($fr('withdrawn'))->toBe('Retirée')
            ->and($fr('unknown_submitter'))->toBe('Compte supprimé');
    });
});
