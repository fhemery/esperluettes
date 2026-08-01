<?php

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Quote\Public\Api\Contracts\AggregateQuoteDto;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function quoteApi(): QuotePublicApi
{
    return app(QuotePublicApi::class);
}

describe('chapter aggregate — count', function () {
    it('counts only live quotes of the chapter', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $otherChapter = createPublishedChapter($this, $story, $author);

        createQuote($reader->id, $chapter->id, $story->id);
        createQuote($reader->id, $chapter->id, $story->id);
        $softDeleted = createQuote($reader->id, $chapter->id, $story->id);
        $softDeleted->delete();
        createQuote($reader->id, $otherChapter->id, $story->id);

        expect(quoteApi()->countForChapter($chapter->id))->toBe(2);
    });

    it('returns zero for a chapter with no quotes', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        expect(quoteApi()->countForChapter($chapter->id))->toBe(0);
    });
});

describe('chapter aggregate — rows', function () {
    it('returns rows newest first with the quoter profile resolved', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $older = createQuote($reader->id, $chapter->id, $story->id, [
            'highlighted_text' => 'Older passage',
        ]);
        DB::table('quotes')->where('id', $older->id)->update(['created_at' => now()->subDay()]);

        $newer = createQuote($reader->id, $chapter->id, $story->id, [
            'highlighted_text' => 'Newer passage',
        ]);

        $aggregate = quoteApi()->getChapterAggregate($chapter->id);

        expect($aggregate->totalCount)->toBe(2);
        expect(array_map(fn($i) => $i->id, $aggregate->items))
            ->toBe([(int) $newer->id, (int) $older->id]);
        expect($aggregate->items[0]->highlightedText)->toBe('Newer passage');
        expect($aggregate->items[0]->prefix)->toBe('words before');
        expect($aggregate->items[0]->suffix)->toBe('words after');
        expect($aggregate->items[0]->quoter->user_id)->toBe($reader->id);
    });

    it('excludes soft-deleted quotes and quotes of other chapters', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $otherChapter = createPublishedChapter($this, $story, $author);

        $live = createQuote($reader->id, $chapter->id, $story->id);
        $softDeleted = createQuote($reader->id, $chapter->id, $story->id);
        $softDeleted->delete();
        createQuote($reader->id, $otherChapter->id, $story->id);

        $aggregate = quoteApi()->getChapterAggregate($chapter->id);

        expect(array_map(fn($i) => $i->id, $aggregate->items))->toBe([(int) $live->id]);
        expect($aggregate->totalCount)->toBe(1);
    });

    it('returns an empty aggregate for a chapter with no quotes', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $aggregate = quoteApi()->getChapterAggregate($chapter->id);

        expect($aggregate->items)->toBe([]);
        expect($aggregate->totalCount)->toBe(0);
    });

    it('omits the quotes of a deactivated reader', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id);

        event(new UserDeactivated($reader->id));

        expect(quoteApi()->getChapterAggregate($chapter->id)->items)->toBe([]);
        expect(quoteApi()->countForChapter($chapter->id))->toBe(0);
    });

    it('includes them again once the reader is reactivated', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id);

        event(new UserDeactivated($reader->id));
        event(new UserReactivated($reader->id));

        expect(quoteApi()->getChapterAggregate($chapter->id)->items)->toHaveCount(1);
        expect(quoteApi()->countForChapter($chapter->id))->toBe(1);
    });

    it('resolves every quoter profile in a single batched call', function () {
        $author = alice($this);
        $reader = bob($this);
        $secondReader = carol($this);
        $thirdReader = daniel($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        foreach ([$reader, $secondReader, $thirdReader] as $quoter) {
            createQuote($quoter->id, $chapter->id, $story->id);
        }

        $spy = Mockery::mock(ProfilePublicApi::class, app(ProfilePublicApi::class));
        $spy->shouldReceive('getPublicProfiles')
            ->once()
            ->andReturnUsing(fn(array $ids) => collect($ids)
                ->mapWithKeys(fn($id) => [$id => new ProfileDto($id, 'Name ' . $id, 'slug-' . $id, '')])
                ->all());
        app()->instance(ProfilePublicApi::class, $spy);

        $aggregate = quoteApi()->getChapterAggregate($chapter->id);

        expect($aggregate->items)->toHaveCount(3);
    });

    it('skips a row whose quoter profile cannot be resolved', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id);

        $spy = Mockery::mock(ProfilePublicApi::class, app(ProfilePublicApi::class));
        $spy->shouldReceive('getPublicProfiles')->andReturn([]);
        app()->instance(ProfilePublicApi::class, $spy);

        expect(quoteApi()->getChapterAggregate($chapter->id)->items)->toBe([]);
    });
});

describe('chapter aggregate — note privacy', function () {
    it('never exposes a note on the aggregate dto', function () {
        $reflection = new ReflectionClass(AggregateQuoteDto::class);

        $names = array_map(
            fn(ReflectionProperty $p) => strtolower($p->getName()),
            $reflection->getProperties(),
        );

        expect($names)->not->toContain('note');
        expect(array_filter($names, fn($n) => str_contains($n, 'note')))->toBe([]);
    });

    it('never selects the note column', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id, ['note' => 'a private note']);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        quoteApi()->getChapterAggregate($chapter->id);
        quoteApi()->countForChapter($chapter->id);

        $quoteQueries = array_filter($queries, fn($sql) => str_contains($sql, 'quotes'));

        expect($quoteQueries)->not->toBeEmpty();
        foreach ($quoteQueries as $sql) {
            expect($sql)->not->toContain('note');
        }
    });
});
