<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestVote;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestVoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Anonymity is a query-shape guarantee, not a template one (architecture §3.3).
 * These tests therefore assert twice: once on the **response body**, which is
 * what a reader can actually see, and once on the **object graph** the template
 * receives, which is what makes a future template mistake harmless.
 *
 * @return array<int, string> every property name reachable from a view model
 */
function ballotPropertyNames(object $contest, int $userId): array
{
    $names = [];

    $walk = function ($value) use (&$walk, &$names): void {
        if (is_array($value)) {
            foreach ($value as $item) {
                $walk($item);
            }

            return;
        }

        if (! is_object($value)) {
            return;
        }

        foreach (get_object_vars($value) as $property => $nested) {
            $names[] = $property;
            $walk($nested);
        }
    };

    $walk(app(QuoteContestVoteService::class)->ballotFor($contest->id, $userId));

    return array_values(array_unique($names));
}

describe('The vote tab never names a submitter', function () {

    it('shows no submitter name for an entry the reader did not submit', function () {
        // Decision #2: anonymous to everyone but admins and moderators.
        $author = alice($this, ['name' => 'Autrice Publique', 'email' => 'autrice@example.com']);
        $reader = bob($this);
        $submitter = carol($this, ['name' => 'Soumettrice Discrete', 'email' => 'soumettrice@example.com']);

        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, [
            'user_id' => $submitter->id,
            'highlighted_text' => 'Un passage soumis par quelqu un d autre',
            'author_user_ids' => [$author->id],
        ]);

        $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertSee('Un passage soumis par quelqu un d autre', false)
            // The story's author is named — that is public. The submitter is not.
            ->assertSee('Autrice Publique', false)
            ->assertDontSee('Soumettrice Discrete', false);
    });

    it('shows no submitter name for the reader own entry either', function () {
        // Nothing may hint at which entry is the reader's (decision #3). The
        // reader's own name legitimately appears in the top bar, so the
        // assertion is a comparison: the page must read *identically* whether
        // the entry is theirs or somebody else's.
        $author = alice($this, ['name' => 'Autrice Publique', 'email' => 'autrice@example.com']);
        $reader = bob($this, ['name' => 'Lecteur Anonyme', 'email' => 'lecteur@example.com']);
        $other = carol($this, ['name' => 'Soumettrice Discrete', 'email' => 'soumettrice@example.com']);

        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, [
            'user_id' => $reader->id,
            'highlighted_text' => 'Mon propre passage en lice',
            'author_user_ids' => [$author->id],
        ]);

        $mine = $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertSee('Mon propre passage en lice', false)
            ->getContent();

        $entry->update(['user_id' => $other->id]);

        $theirs = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect(substr_count($mine, 'Lecteur Anonyme'))
            ->toBe(substr_count($theirs, 'Lecteur Anonyme'))
            ->and($mine)->not->toContain('Soumettrice Discrete')
            ->and($theirs)->not->toContain('Soumettrice Discrete');
    });

    it('carries no submitter id in the objects the ballot template receives', function () {
        $reader = bob($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, ['user_id' => carol($this)->id]);

        $properties = ballotPropertyNames($contest, $reader->id);

        expect($properties)->not->toContain('userId')
            ->and($properties)->not->toContain('user_id')
            ->and($properties)->not->toContain('submitterId')
            ->and($properties)->not->toContain('submitterName');
    });
});

describe('The vote tab never shows a vote count', function () {

    it('shows no vote count in any phase', function () {
        // Decision #6: counts live only in the moderator Résultats tab.
        $reader = bob($this);
        $submitter = carol($this);
        $voters = [daniel($this), alice($this)];

        $contests = [
            'submissions' => createContestInSubmissions($this),
            'voting' => createContestInVoting($this),
            'ended' => createContestEnded($this),
        ];

        foreach ($contests as $contest) {
            $category = makeCategory($contest->id, 'La plus drôle');
            $entry = makeEntryIn($category, [
                'user_id' => $submitter->id,
                'highlighted_text' => 'Un passage très voté',
            ]);

            foreach ($voters as $voter) {
                QuoteContestVote::create([
                    'category_id' => $category->id,
                    'entry_id' => $entry->id,
                    'user_id' => $voter->id,
                ]);
            }

            $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

            // Nothing in the page speaks of a count, and — the guarantee that
            // matters — nothing in the object graph holds one.
            expect($html)->not->toContain('quote-contest::quote-contest.votes.count')
                ->and($html)->not->toContain('vote_count')
                ->and($html)->not->toContain('votes_count');

            $properties = ballotPropertyNames($contest, $reader->id);

            expect($properties)->not->toContain('voteCount')
                ->and($properties)->not->toContain('votes')
                ->and($properties)->not->toContain('votesCount')
                ->and($properties)->not->toContain('tally');
        }
    });
});
