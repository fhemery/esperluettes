<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestVote;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestVoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function castVote(TestCase $t, object $contest, int $categoryId, int $entryId)
{
    return $t->put(route('quote-contest.votes.update', [$contest->id, $categoryId]), [
        'entry_id' => $entryId,
    ]);
}

/** @return array<int, \App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\VoteCategoryViewModel> */
function ballotFor(object $contest, int $userId): array
{
    return app(QuoteContestVoteService::class)->ballotFor($contest->id, $userId);
}

describe('Casting a ballot', function () {

    it('lets a confirmed user cast one vote in a category', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => carol($this)->id]);

        $this->actingAs($reader);
        castVote($this, $contest, (int) $category->id, (int) $entry->id)->assertRedirect();

        $vote = QuoteContestVote::query()->sole();

        expect((int) $vote->category_id)->toBe((int) $category->id)
            ->and((int) $vote->entry_id)->toBe((int) $entry->id)
            ->and((int) $vote->user_id)->toBe($reader->id);
    });

    it('updates the existing row when a reader changes their vote', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $first = makeEntryIn($category, ['user_id' => carol($this)->id]);
        $second = makeEntryIn($category, ['user_id' => daniel($this)->id]);

        $this->actingAs($reader);
        castVote($this, $contest, (int) $category->id, (int) $first->id)->assertRedirect();
        $voteId = (int) QuoteContestVote::query()->sole()->id;

        castVote($this, $contest, (int) $category->id, (int) $second->id)->assertRedirect();

        $vote = QuoteContestVote::query()->sole();

        expect((int) $vote->id)->toBe($voteId)
            ->and((int) $vote->entry_id)->toBe((int) $second->id);
    });

    it('never creates a second row for a second ballot in the same category', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $first = makeEntryIn($category, ['user_id' => carol($this)->id]);
        $second = makeEntryIn($category, ['user_id' => daniel($this)->id]);

        $this->actingAs($reader);

        foreach ([$first, $second, $first, $second] as $target) {
            castVote($this, $contest, (int) $category->id, (int) $target->id)->assertRedirect();
        }

        expect(QuoteContestVote::query()->count())->toBe(1);
    });

    it('holds at most one ballot per reader per category, at the database level', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $first = makeEntryIn($category, ['user_id' => carol($this)->id]);
        $second = makeEntryIn($category, ['user_id' => daniel($this)->id]);

        QuoteContestVote::create([
            'category_id' => $category->id,
            'entry_id' => $first->id,
            'user_id' => $reader->id,
        ]);

        // The unique index is real this time (§2.1): bypassing the service and
        // inserting a second ballot is refused by the database itself.
        expect(fn () => QuoteContestVote::create([
            'category_id' => $category->id,
            'entry_id' => $second->id,
            'user_id' => $reader->id,
        ]))->toThrow(Illuminate\Database\QueryException::class);
    });

    it('lets a reader vote in each category independently', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $funniest = makeCategory($contest->id, 'La plus drôle', 1);
        $saddest = makeCategory($contest->id, 'La plus émouvante', 2);
        $inFunniest = makeEntryIn($funniest, ['user_id' => carol($this)->id]);
        $inSaddest = makeEntryIn($saddest, ['user_id' => daniel($this)->id]);

        $this->actingAs($reader);
        castVote($this, $contest, (int) $funniest->id, (int) $inFunniest->id)->assertRedirect();
        castVote($this, $contest, (int) $saddest->id, (int) $inSaddest->id)->assertRedirect();

        expect(QuoteContestVote::query()->where('user_id', $reader->id)->count())->toBe(2);
    });

    it('lets a reader vote for their own entry', function () {
        // Decision #3: blocking it would only hint at which entry is theirs.
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $mine = makeEntryIn($category, ['user_id' => $reader->id]);

        $this->actingAs($reader);
        castVote($this, $contest, (int) $category->id, (int) $mine->id)->assertRedirect();

        expect((int) QuoteContestVote::query()->sole()->entry_id)->toBe((int) $mine->id);
    });

    it('records nothing for a reader who does not vote', function () {
        // Assumption A10: "has not voted" and "chose not to vote" are the same
        // absent row — there is no abstention to cast.
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, ['user_id' => carol($this)->id]);

        $this->actingAs($reader)->get($contest->url)->assertOk();

        expect(QuoteContestVote::query()->count())->toBe(0)
            ->and(ballotFor($contest, $reader->id)[0]->hasVoted())->toBeFalse();
    });
});

describe('Voting — what the server refuses', function () {

    it('refuses a vote before the vote phase opens', function () {
        $reader = bob($this);
        $submitter = carol($this);

        foreach ([createContestBeforeStart($this), createContestInSubmissions($this), createContestInInterlude($this)] as $contest) {
            $category = makeCategory($contest->id, 'La plus drôle');
            $entry = makeEntryIn($category, ['user_id' => $submitter->id]);

            $this->actingAs($reader);
            castVote($this, $contest, (int) $category->id, (int) $entry->id)->assertForbidden();
        }

        expect(QuoteContestVote::query()->count())->toBe(0);
    });

    it('refuses a vote once the activity has ended', function () {
        $reader = bob($this);
        $contest = createContestEnded($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => carol($this)->id]);

        $this->actingAs($reader);
        castVote($this, $contest, (int) $category->id, (int) $entry->id)->assertForbidden();

        expect(QuoteContestVote::query()->count())->toBe(0);
    });

    it('refuses a vote for an entry of another category', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $funniest = makeCategory($contest->id, 'La plus drôle', 1);
        $saddest = makeCategory($contest->id, 'La plus émouvante', 2);
        $elsewhere = makeEntryIn($saddest, ['user_id' => carol($this)->id]);

        $this->actingAs($reader);
        castVote($this, $contest, (int) $funniest->id, (int) $elsewhere->id)->assertForbidden();

        expect(QuoteContestVote::query()->count())->toBe(0);
    });

    it('refuses a vote for a withdrawn entry', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $withdrawn = makeEntryIn($category, [
            'user_id' => carol($this)->id,
            'withdrawn_at' => now()->subHour(),
        ]);

        $this->actingAs($reader);
        castVote($this, $contest, (int) $category->id, (int) $withdrawn->id)->assertForbidden();

        expect(QuoteContestVote::query()->count())->toBe(0);
    });

    it('refuses a vote in a category of another contest', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        makeCategory($contest->id, 'La plus drôle');

        // Another contest, still taking submissions: its ballot is not open,
        // whichever URL the request borrows.
        $elsewhere = createContestInSubmissions($this, ['name' => 'Un autre concours']);
        $foreign = makeCategory($elsewhere->id, 'Catégorie étrangère');
        $entry = makeEntryIn($foreign, ['user_id' => carol($this)->id]);

        $this->actingAs($reader);
        castVote($this, $contest, (int) $foreign->id, (int) $entry->id)->assertForbidden();

        expect(QuoteContestVote::query()->count())->toBe(0);
    });

    it('refuses a request without an entry', function () {
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs(bob($this))
            ->put(route('quote-contest.votes.update', [$contest->id, $category->id]), [])
            ->assertSessionHasErrors(['entry_id']);

        expect(QuoteContestVote::query()->count())->toBe(0);
    });

    it('refuses a vote from a guest', function () {
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => carol($this)->id]);

        castVote($this, $contest, (int) $category->id, (int) $entry->id)->assertRedirect(route('login'));

        expect(QuoteContestVote::query()->count())->toBe(0);
    });
});

describe('The ballot listing', function () {

    it('shows every non-withdrawn entry of every category', function () {
        $reader = bob($this);
        $submitter = carol($this);
        $contest = createContestInVoting($this);
        $funniest = makeCategory($contest->id, 'La plus drôle', 1);
        $saddest = makeCategory($contest->id, 'La plus émouvante', 2);

        makeEntryIn($funniest, ['user_id' => $submitter->id, 'highlighted_text' => 'Le passage le plus drôle']);
        makeEntryIn($saddest, ['user_id' => $submitter->id, 'highlighted_text' => 'Le passage le plus triste']);

        $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertSee('Le passage le plus drôle', false)
            ->assertSee('Le passage le plus triste', false)
            ->assertSee('quote-contest::quote-contest.tab_votes', false);
    });

    it('leaves a withdrawn entry out of the vote listing', function () {
        $reader = bob($this);
        $submitter = carol($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        makeEntryIn($category, ['user_id' => $submitter->id, 'highlighted_text' => 'La citation en lice']);
        makeEntryIn($category, [
            'user_id' => daniel($this)->id,
            'highlighted_text' => 'La citation retirée',
            'withdrawn_at' => now()->subHour(),
        ]);

        $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertSee('La citation en lice', false)
            ->assertDontSee('La citation retirée', false);

        expect(ballotFor($contest, $reader->id)[0]->entries)->toHaveCount(1);
    });

    it('renders an empty category as empty, votable by nobody', function () {
        // Assumption A8: no minimum entry count, nothing marks a winner.
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'Catégorie déserte');

        $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertSee('quote-contest::quote-contest.votes.no_entries', false);

        expect(ballotFor($contest, $reader->id)[0]->isEmpty())->toBeTrue();

        // And a forged ballot in it finds no entry to vote for.
        castVote($this, $contest, (int) $category->id, 999999)->assertForbidden();

        expect(QuoteContestVote::query()->count())->toBe(0);
    });

    it('marks each category as voted or not voted', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $funniest = makeCategory($contest->id, 'La plus drôle', 1);
        $saddest = makeCategory($contest->id, 'La plus émouvante', 2);
        $entry = makeEntryIn($funniest, ['user_id' => carol($this)->id]);
        makeEntryIn($saddest, ['user_id' => carol($this)->id]);

        $this->actingAs($reader);
        castVote($this, $contest, (int) $funniest->id, (int) $entry->id)->assertRedirect();

        $ballot = ballotFor($contest, $reader->id);

        expect($ballot[0]->hasVoted())->toBeTrue()
            ->and($ballot[0]->myVoteEntryId)->toBe((int) $entry->id)
            ->and($ballot[1]->hasVoted())->toBeFalse()
            ->and($ballot[1]->myVoteEntryId)->toBeNull();

        $this->get($contest->url)->assertOk()
            ->assertSee('quote-contest::quote-contest.votes.voted', false)
            ->assertSee('quote-contest::quote-contest.votes.not_voted', false);
    });

    it('never shows another reader ballot as one own', function () {
        $reader = bob($this);
        $other = carol($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => daniel($this)->id]);

        $this->actingAs($other);
        castVote($this, $contest, (int) $category->id, (int) $entry->id)->assertRedirect();

        expect(ballotFor($contest, $reader->id)[0]->hasVoted())->toBeFalse();
    });

    it('shows the passage, the story link, the chapter link and the author names', function () {
        // The user's display name lives on their profile, so the assertion
        // names it literally rather than reading `$author->name`, which is null.
        $author = alice($this, ['name' => 'Autrice En Lice', 'email' => 'autrice@example.com']);
        $reader = bob($this);
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

        $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertSee('Un passage en lice', false)
            ->assertSee('Une histoire en lice', false)
            ->assertSee('Un chapitre en lice', false)
            ->assertSee(route('stories.show', ['slug' => '3-une-histoire-en-lice']), false)
            ->assertSee(route('chapters.show', [
                'storySlug' => '3-une-histoire-en-lice',
                'chapterSlug' => '11-un-chapitre-en-lice',
            ]), false)
            // Decision #19: names are resolved live, never frozen in the row.
            ->assertSee('Autrice En Lice', false);
    });

    it('omits an author whose profile no longer resolves, and keeps the entry', function () {
        // Decision #19: a deleted author resolves to null and is omitted.
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, [
            'user_id' => carol($this)->id,
            'highlighted_text' => 'Un passage orphelin',
            'author_user_ids' => [999999],
        ]);

        $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertSee('Un passage orphelin', false);

        expect(ballotFor($contest, $reader->id)[0]->entries[0]->authorNames)->toBe([]);
    });
});

describe('The ballot order', function () {

    it('returns the same order to the same reader on every reload', function () {
        // Decision #22, asserted through the real listing and not the pure
        // function: the shuffle must survive the query's own ordering.
        $reader = bob($this);
        $submitter = carol($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        for ($i = 1; $i <= 8; $i++) {
            makeEntryIn($category, ['user_id' => $submitter->id, 'highlighted_text' => 'Passage ' . $i]);
        }

        $ids = fn (int $userId) => array_map(
            static fn ($entry) => $entry->id,
            ballotFor($contest, $userId)[0]->entries,
        );

        expect($ids($reader->id))->toBe($ids($reader->id))
            ->and($ids($reader->id))->toHaveCount(8);
    });

    it('gives two readers different orders', function () {
        $reader = bob($this);
        $other = carol($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        for ($i = 1; $i <= 8; $i++) {
            makeEntryIn($category, ['user_id' => daniel($this)->id, 'highlighted_text' => 'Passage ' . $i]);
        }

        $ids = fn (int $userId) => array_map(
            static fn ($entry) => $entry->id,
            ballotFor($contest, $userId)[0]->entries,
        );

        expect($ids($other->id))->not->toBe($ids($reader->id));
    });
});

describe('The ballot across the phases', function () {

    it('offers no ballot before the votes open', function () {
        $reader = bob($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, ['user_id' => carol($this)->id, 'highlighted_text' => 'Un passage pas encore votable']);

        $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('quote-contest::quote-contest.votes.opens_at')
            ->and($html)->not->toContain('Un passage pas encore votable')
            ->and($html)->not->toContain('quote-contest::quote-contest.votes.cast');
    });

    it('turns the ballot read-only once the activity has ended', function () {
        // Spec §4.5.8: the reader still sees their own vote, and no result.
        $reader = bob($this);
        $contest = createContestEnded($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => carol($this)->id, 'highlighted_text' => 'Le passage que j ai choisi']);

        QuoteContestVote::create([
            'category_id' => $category->id,
            'entry_id' => $entry->id,
            'user_id' => $reader->id,
        ]);

        $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('quote-contest::quote-contest.votes.closed')
            ->toContain('Le passage que j ai choisi')
            // The fieldset is disabled and no submit button is offered.
            ->toContain('quote-contest::quote-contest.votes.voted')
            ->and($html)->not->toContain('quote-contest::quote-contest.votes.cast');
    });
});

describe('Votes — French wording', function () {

    it('words every reader-facing vote message in French', function () {
        $fr = fn (string $key) => trans('quote-contest::quote-contest.votes.' . $key, [], 'fr');

        expect($fr('voted'))->toBe('Vous avez voté')
            ->and($fr('not_voted'))->toBe('Vous n\'avez pas voté')
            ->and($fr('cast'))->toBe('Voter')
            ->and(trans('quote-contest::quote-contest.tab_votes', [], 'fr'))->toBe('Votes');
    });
});
