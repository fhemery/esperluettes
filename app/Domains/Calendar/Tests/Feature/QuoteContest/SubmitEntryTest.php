<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestEntry;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * A public story with one published chapter, ready to be quoted from.
 *
 * Returns an object: { story: Story, chapter: Chapter }
 */
function submittableStory(TestCase $t, Authenticatable $author, string $title, array $storyAttributes = []): object
{
    $story = publicStory($title, $author->id, $storyAttributes);

    return (object) [
        'story' => $story,
        'chapter' => createPublishedChapter($t, $story, $author, ['title' => 'Chapitre premier']),
    ];
}

function submitEntry(TestCase $t, object $contest, int $categoryId, int $quoteId)
{
    return $t->post(route('quote-contest.entries.store', $contest->id), [
        'category_id' => $categoryId,
        'quote_id' => $quoteId,
    ]);
}

function withdrawEntry(TestCase $t, object $contest, int $entryId)
{
    return $t->delete(route('quote-contest.entries.destroy', [$contest->id, $entryId]));
}

describe('Submitting a quote', function () {

    it('lets a confirmed user submit an eligible quote to a category', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id, [
            'highlighted_text' => 'Un passage à soumettre',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $quote->id)->assertRedirect();

        $entry = QuoteContestEntry::query()->sole();

        expect((int) $entry->activity_id)->toBe($contest->id)
            ->and((int) $entry->category_id)->toBe($category->id)
            ->and((int) $entry->user_id)->toBe($reader->id)
            ->and((int) $entry->quote_id)->toBe($quote->id)
            ->and($entry->withdrawn_at)->toBeNull();

        // And the reader now sees it as their entry in that category.
        $this->get($contest->url)->assertOk()->assertSee('Un passage à soumettre', false);
    });

    it('snapshots the passage, the story and the chapter', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id, [
            'highlighted_text' => 'Un passage mémorable',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $quote->id)->assertRedirect();

        $entry = QuoteContestEntry::query()->sole();

        expect($entry->highlighted_text)->toBe('Un passage mémorable')
            ->and($entry->story_title)->toBe('Mon histoire')
            ->and($entry->story_slug)->toBe($source->story->slug)
            ->and((int) $entry->story_id)->toBe($source->story->id)
            ->and((int) $entry->chapter_id)->toBe($source->chapter->id)
            ->and($entry->chapter_title)->toBe('Chapitre premier')
            ->and($entry->chapter_slug)->toBe($source->chapter->slug)
            ->and($entry->author_user_ids)->toBe([$author->id]);
    });

    it('never stores the private note, in the table nor on the page', function () {
        // Assumption A1: the note never enters the contest, anywhere.
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id, [
            'highlighted_text' => 'Un passage sans sa note',
            'note' => 'Ma note strictement personnelle',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $quote->id)->assertRedirect();

        $columns = Schema::getColumnListing('calendar_quote_contest_entries');
        expect($columns)->not->toContain('note');

        $entry = QuoteContestEntry::query()->sole();
        expect(json_encode($entry->getAttributes(), JSON_UNESCAPED_UNICODE))
            ->not->toContain('Ma note strictement personnelle');

        $this->get($contest->url)->assertOk()
            ->assertSee('Un passage sans sa note', false)
            ->assertDontSee('Ma note strictement personnelle', false);
    });

    it('replaces the sitting entry when submitting into an occupied category', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $first = createQuote($reader->id, $source->chapter->id, $source->story->id, [
            'highlighted_text' => 'La première proposition',
        ]);
        $second = createQuote($reader->id, $source->chapter->id, $source->story->id, [
            'highlighted_text' => 'La seconde proposition',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $first->id)->assertRedirect();
        $firstEntryId = (int) QuoteContestEntry::query()->sole()->id;

        submitEntry($this, $contest, $category->id, $second->id)->assertRedirect();

        // One entry per (category, user): the sitting row is hard-deleted.
        $entry = QuoteContestEntry::query()->sole();

        expect((int) $entry->quote_id)->toBe($second->id)
            ->and($entry->highlighted_text)->toBe('La seconde proposition')
            ->and(QuoteContestEntry::query()->whereKey($firstEntryId)->exists())->toBeFalse();
    });

    it('leaves a withdrawn entry in place and frees its category slot', function () {
        // Decision #18 / §2.3: a privacy-withdrawn row is evidence and stays,
        // but it no longer occupies the slot.
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id, [
            'highlighted_text' => 'La citation de remplacement',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $withdrawn = makeEntryIn($category, [
            'user_id' => $reader->id,
            'highlighted_text' => 'La citation retirée',
            'withdrawn_at' => now()->subHour(),
        ]);

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $quote->id)->assertRedirect();

        expect(QuoteContestEntry::query()->count())->toBe(2)
            ->and(QuoteContestEntry::query()->whereKey($withdrawn->id)->exists())->toBeTrue()
            ->and(QuoteContestEntry::query()->whereNull('withdrawn_at')->sole()->highlighted_text)
            ->toBe('La citation de remplacement');
    });

    it('allows the same quote in several categories', function () {
        // Spec §4.3.7.
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id);

        $contest = createContestInSubmissions($this);
        $funniest = makeCategory($contest->id, 'La plus drôle', 1);
        $saddest = makeCategory($contest->id, 'La plus émouvante', 2);

        $this->actingAs($reader);
        submitEntry($this, $contest, $funniest->id, $quote->id)->assertRedirect();
        submitEntry($this, $contest, $saddest->id, $quote->id)->assertRedirect();

        expect(QuoteContestEntry::query()->where('quote_id', $quote->id)->count())->toBe(2);
    });

    it('allows two readers to enter the same passage in one category', function () {
        // Decision #13.
        $author = alice($this);
        $reader = bob($this);
        $other = carol($this);
        $source = submittableStory($this, $author, 'Mon histoire');

        $mine = createQuote($reader->id, $source->chapter->id, $source->story->id, [
            'highlighted_text' => 'Le même passage',
        ]);
        $theirs = createQuote($other->id, $source->chapter->id, $source->story->id, [
            'highlighted_text' => 'Le même passage',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $mine->id)->assertRedirect();

        $this->actingAs($other);
        submitEntry($this, $contest, $category->id, $theirs->id)->assertRedirect();

        expect(QuoteContestEntry::query()->where('category_id', $category->id)->count())->toBe(2);
    });
});

describe('Submitting — what the server refuses', function () {

    it('refuses a quote the caller does not own', function () {
        $author = alice($this);
        $reader = bob($this);
        $other = carol($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $theirs = createQuote($other->id, $source->chapter->id, $source->story->id);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $theirs->id)->assertForbidden();

        expect(QuoteContestEntry::query()->count())->toBe(0);
    });

    it('refuses an ineligible quote even when the request is forged', function () {
        // The picker's greying is a courtesy; this is the enforcement.
        $author = alice($this);
        $reader = bob($this);

        $secret = privateStory('Histoire secrète', $author->id);
        $secretChapter = createPublishedChapter($this, $secret, $author);
        $secretQuote = createQuote($reader->id, $secretChapter->id, $secret->id);

        $hidden = submittableStory($this, $author, 'Histoire hors concours', ['is_excluded_from_events' => true]);
        $hiddenQuote = createQuote($reader->id, $hidden->chapter->id, $hidden->story->id);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $secretQuote->id)->assertForbidden();
        submitEntry($this, $contest, $category->id, $hiddenQuote->id)->assertForbidden();

        expect(QuoteContestEntry::query()->count())->toBe(0);
    });

    it('refuses a category that belongs to another contest', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id);

        $contest = createContestInSubmissions($this);
        makeCategory($contest->id, 'La plus drôle');

        $elsewhere = createContestInSubmissions($this, ['name' => 'Un autre concours']);
        $foreign = makeCategory($elsewhere->id, 'Catégorie étrangère');

        $this->actingAs($reader);
        submitEntry($this, $contest, $foreign->id, $quote->id)->assertForbidden();

        expect(QuoteContestEntry::query()->count())->toBe(0);
    });

    it('refuses a submission before the contest starts', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id);

        $contest = createContestBeforeStart($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $quote->id)->assertForbidden();

        expect(QuoteContestEntry::query()->count())->toBe(0);
    });

    it('refuses a submission once submissions have closed', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id);

        foreach ([createContestInInterlude($this), createContestInVoting($this), createContestEnded($this)] as $contest) {
            $category = makeCategory($contest->id, 'La plus drôle');

            $this->actingAs($reader);
            submitEntry($this, $contest, $category->id, $quote->id)->assertForbidden();
        }

        expect(QuoteContestEntry::query()->count())->toBe(0);
    });

    it('refuses a request without a category or a quote', function () {
        $contest = createContestInSubmissions($this);

        $this->actingAs(bob($this))
            ->post(route('quote-contest.entries.store', $contest->id), [])
            ->assertSessionHasErrors(['category_id', 'quote_id']);

        expect(QuoteContestEntry::query()->count())->toBe(0);
    });
});

describe('Withdrawing an entry', function () {

    it('lets a reader withdraw an entry without replacing it', function () {
        // Decision #12.
        $reader = bob($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, [
            'user_id' => $reader->id,
            'highlighted_text' => 'Une citation à retirer',
        ]);

        $this->actingAs($reader);
        withdrawEntry($this, $contest, (int) $entry->id)->assertRedirect();

        expect(QuoteContestEntry::query()->count())->toBe(0);

        $this->get($contest->url)->assertOk()->assertDontSee('Une citation à retirer', false);
    });

    it('refuses to withdraw another reader entry', function () {
        $reader = bob($this);
        $other = carol($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => $other->id]);

        $this->actingAs($reader);
        withdrawEntry($this, $contest, (int) $entry->id)->assertForbidden();

        expect(QuoteContestEntry::query()->whereKey($entry->id)->exists())->toBeTrue();
    });

    it('refuses to withdraw once submissions have closed', function () {
        $reader = bob($this);
        $contest = createContestInInterlude($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => $reader->id]);

        $this->actingAs($reader);
        withdrawEntry($this, $contest, (int) $entry->id)->assertForbidden();

        expect(QuoteContestEntry::query()->whereKey($entry->id)->exists())->toBeTrue();
    });
});

describe('The submission surface across the phases', function () {

    it('offers submit and withdraw only while submissions are open', function () {
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        createQuote($reader->id, $source->chapter->id, $source->story->id);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('quote-contest::quote-contest.my_quotes.submit');

        makeEntryIn($category, ['user_id' => $reader->id]);
        $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('quote-contest::quote-contest.my_quotes.replace')
            ->and($html)->toContain('quote-contest::quote-contest.my_quotes.withdraw')
            // The replace confirmation is a modal, never a JS confirm().
            ->and($html)->toContain('qc-replace-' . $category->id)
            ->and($html)->not->toContain('onclick="return confirm');
    });

    it('is read-only during the interlude and shows the countdown to the vote', function () {
        // Spec §4.4.
        $reader = bob($this);
        $contest = createContestInInterlude($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, ['user_id' => $reader->id, 'highlighted_text' => 'Ma citation en lice']);

        $html = $this->actingAs($reader)->get($contest->url)->assertOk()->getContent();

        expect($html)->toContain('quote-contest::quote-contest.phase.interlude')
            ->and($html)->toContain('Ma citation en lice')
            ->and($html)->not->toContain('quote-contest::quote-contest.my_quotes.submit')
            ->and($html)->not->toContain('quote-contest::quote-contest.my_quotes.replace')
            ->and($html)->not->toContain('quote-contest::quote-contest.my_quotes.withdraw');
    });
});

describe('The snapshot outlives its source', function () {

    it('leaves the entry untouched when the source quote is edited or deleted', function () {
        // Spec §5, decision #4: the entry holds no live reference it reads.
        $author = alice($this);
        $reader = bob($this);
        $source = submittableStory($this, $author, 'Mon histoire');
        $quote = createQuote($reader->id, $source->chapter->id, $source->story->id, [
            'highlighted_text' => 'Un passage qui survit',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($reader);
        submitEntry($this, $contest, $category->id, $quote->id)->assertRedirect();

        $before = QuoteContestEntry::query()->sole()->only([
            'quote_id', 'story_id', 'highlighted_text', 'story_title', 'story_slug',
            'chapter_id', 'chapter_title', 'chapter_slug', 'withdrawn_at',
        ]);

        $api = app(QuotePublicApi::class);
        $api->updateNote($quote->id, $reader->id, 'Une note ajoutée après coup');
        $api->delete($quote->id, $reader->id);

        $after = QuoteContestEntry::query()->sole();

        expect($after->only(array_keys($before)))->toBe($before);

        $this->get($contest->url)->assertOk()
            ->assertSee('Un passage qui survit', false)
            ->assertDontSee('Une note ajoutée après coup', false);
    });
});
