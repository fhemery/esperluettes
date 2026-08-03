<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestEntry;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestSubmissionService;
use App\Domains\Story\Public\Contracts\StoryVisibility;
use App\Domains\Story\Public\Events\StoryExcludedFromEvents;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
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
function eligibleStory(TestCase $t, Authenticatable $author, string $title): object
{
    $story = publicStory($title, $author->id);

    return (object) [
        'story' => $story,
        'chapter' => createPublishedChapter($t, $story, $author, ['title' => 'Chapitre premier']),
    ];
}

/** The real event Story emits when an author tightens or loosens visibility. */
function changeStoryVisibility(int $storyId, string $from, string $to): void
{
    setStoryVisibility($storyId, $to);
    dispatchEvent(new StoryVisibilityChanged(
        storyId: $storyId,
        title: 'Une histoire',
        oldVisibility: $from,
        newVisibility: $to,
    ));
}

function excludeStoryFromEvents(int $storyId): void
{
    DB::table('stories')->where('id', $storyId)->update(['is_excluded_from_events' => true]);
    dispatchEvent(new StoryExcludedFromEvents(storyId: $storyId, title: 'Une histoire'));
}

describe('Withdrawal when a quoted story loses eligibility', function () {

    it('withdraws every entry of a story turned private, and nothing else', function () {
        $reader = bob($this);
        $contest = createContestInSubmissions($this);
        $funniest = makeCategory($contest->id, 'La plus drôle', 1);
        $saddest = makeCategory($contest->id, 'La plus triste', 2);

        // Two entries from the story that goes private, in two categories…
        $doomed = makeEntryIn($funniest, ['user_id' => $reader->id, 'story_id' => 77]);
        $alsoDoomed = makeEntryIn($saddest, ['user_id' => $reader->id, 'story_id' => 77]);
        // …and one from another story, which must not be touched.
        $spared = makeEntryIn($funniest, ['user_id' => 4242, 'story_id' => 88]);

        changeStoryVisibility(77, StoryVisibility::PUBLIC, StoryVisibility::PRIVATE);

        expect($doomed->fresh()->withdrawn_at)->not->toBeNull()
            ->and($alsoDoomed->fresh()->withdrawn_at)->not->toBeNull()
            ->and($spared->fresh()->withdrawn_at)->toBeNull();
    });

    it('leaves an already withdrawn entry on its original stamp', function () {
        $reader = bob($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $stampedAt = now()->subDays(3)->startOfSecond();
        $entry = makeEntryIn($category, [
            'user_id' => $reader->id,
            'story_id' => 77,
            'withdrawn_at' => $stampedAt,
        ]);

        changeStoryVisibility(77, StoryVisibility::PUBLIC, StoryVisibility::PRIVATE);

        expect($entry->fresh()->withdrawn_at->equalTo($stampedAt))->toBeTrue();
    });

    it('does not withdraw when a story moves to community', function () {
        // Assumption A2: `community` is an eligible visibility.
        $reader = bob($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => $reader->id, 'story_id' => 77]);

        changeStoryVisibility(77, StoryVisibility::PUBLIC, StoryVisibility::COMMUNITY);

        expect($entry->fresh()->withdrawn_at)->toBeNull();
    });

    it('withdraws every entry of a story excluded from events', function () {
        $reader = bob($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => $reader->id, 'story_id' => 77]);
        $spared = makeEntryIn($category, ['user_id' => 4242, 'story_id' => 88]);

        excludeStoryFromEvents(77);

        expect($entry->fresh()->withdrawn_at)->not->toBeNull()
            ->and($spared->fresh()->withdrawn_at)->toBeNull();
    });

    it('does not restore an entry when the story returns to public', function () {
        // §2.3: no automatic restore — re-entering is the reader's action.
        $reader = bob($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => $reader->id, 'story_id' => 77]);

        changeStoryVisibility(77, StoryVisibility::PUBLIC, StoryVisibility::PRIVATE);
        $withdrawnAt = $entry->fresh()->withdrawn_at;

        changeStoryVisibility(77, StoryVisibility::PRIVATE, StoryVisibility::PUBLIC);

        expect($entry->fresh()->withdrawn_at)->not->toBeNull()
            ->and($entry->fresh()->withdrawn_at->equalTo($withdrawnAt))->toBeTrue();
    });

    it('is a no-op for a story with no entries', function () {
        $reader = bob($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => $reader->id, 'story_id' => 88]);
        $updatedAt = $entry->fresh()->updated_at;

        $statements = [];
        DB::listen(function ($query) use (&$statements) {
            if (str_contains($query->sql, 'calendar_quote_contest_entries')) {
                $statements[] = $query->sql;
            }
        });

        changeStoryVisibility(77, StoryVisibility::PUBLIC, StoryVisibility::PRIVATE);

        // One indexed UPDATE, no per-row read-then-delete loop (§3.4).
        expect($statements)->toHaveCount(1)
            ->and($statements[0])->toStartWith('update');

        // And nothing was written: the only entry is untouched, timestamp included.
        expect($entry->fresh()->withdrawn_at)->toBeNull()
            ->and($entry->fresh()->updated_at->equalTo($updatedAt))->toBeTrue();
    });
});

describe('What the reader sees after a privacy withdrawal', function () {

    it('hides the withdrawn entry from Mes citations', function () {
        $reader = bob($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, [
            'user_id' => $reader->id,
            'story_id' => 77,
            'highlighted_text' => 'Une citation devenue privée',
        ]);

        $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertSee('Une citation devenue privée', false);

        changeStoryVisibility(77, StoryVisibility::PUBLIC, StoryVisibility::PRIVATE);

        expect(app(QuoteContestSubmissionService::class)->currentEntriesFor($contest->id, $reader->id))->toBe([]);

        $this->actingAs($reader)->get($contest->url)->assertOk()
            ->assertDontSee('Une citation devenue privée', false);
    });

    it('frees the category slot so the reader may enter another quote', function () {
        $author = alice($this);
        $reader = bob($this);
        $doomed = eligibleStory($this, $author, 'Histoire qui devient privée');
        $other = eligibleStory($this, $author, 'Une autre histoire');

        $firstQuote = createQuote($reader->id, $doomed->chapter->id, $doomed->story->id, [
            'highlighted_text' => 'Le passage retiré',
        ]);
        $secondQuote = createQuote($reader->id, $other->chapter->id, $other->story->id, [
            'highlighted_text' => 'Le passage de remplacement',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        $this->post(route('quote-contest.entries.store', $contest->id), [
            'category_id' => $category->id,
            'quote_id' => $firstQuote->id,
        ])->assertRedirect();

        changeStoryVisibility($doomed->story->id, StoryVisibility::PUBLIC, StoryVisibility::PRIVATE);

        // The slot is free again: the same category takes a second entry, and
        // the withdrawn row stays as evidence (§2.3).
        $this->post(route('quote-contest.entries.store', $contest->id), [
            'category_id' => $category->id,
            'quote_id' => $secondQuote->id,
        ])->assertRedirect();

        expect(QuoteContestEntry::query()->count())->toBe(2)
            ->and(QuoteContestEntry::query()->whereNull('withdrawn_at')->count())->toBe(1);

        $entries = app(QuoteContestSubmissionService::class)->currentEntriesFor($contest->id, $reader->id);
        expect($entries[$category->id]->highlightedText)->toBe('Le passage de remplacement');
    });
});
