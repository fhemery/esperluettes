<?php

use App\Domains\Auth\Public\Api\Roles;
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

describe('GET /quotes/chapter-aggregate', function () {
    it('redirects a guest', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->post('/logout');

        $this->get('/quotes/chapter-aggregate?chapter_id=' . $chapter->id)
            ->assertRedirect(route('login'));
    });

    it('forbids a confirmed reader who is not an author', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id)
            ->assertForbidden();
    });

    it('forbids a moderator', function () {
        $author = alice($this);
        $moderator = moderator($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($moderator)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id)
            ->assertForbidden();
    });

    it('forbids an admin', function () {
        $author = alice($this);
        $admin = admin($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($admin)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id)
            ->assertForbidden();
    });

    it('allows the author', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id, [
            'highlighted_text' => 'A memorable passage',
        ]);

        $response = $this->actingAs($author)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id);

        $response->assertOk()
            ->assertJsonPath('total_count', 1)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', (int) $quote->id)
            ->assertJsonPath('items.0.highlighted_text', 'A memorable passage')
            ->assertJsonPath('items.0.prefix', 'words before')
            ->assertJsonPath('items.0.suffix', 'words after')
            ->assertJsonPath('items.0.quoter.user_id', $reader->id);
    });

    it('returns an empty aggregate for a chapter with no quotes', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($author)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id)
            ->assertOk()
            ->assertJsonPath('total_count', 0)
            ->assertJsonCount(0, 'items');
    });

    it('allows a co-author', function () {
        $author = alice($this);
        $coAuthor = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        addCollaborator($story->id, $coAuthor->id, 'author');

        $this->actingAs($coAuthor)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id)
            ->assertOk();
    });

    it('forbids a beta reader of the story', function () {
        $author = alice($this);
        $betaReader = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        addCollaborator($story->id, $betaReader->id, 'beta-reader');

        $this->actingAs($betaReader)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id)
            ->assertForbidden();
    });

    it('denies an author who is not a confirmed user', function () {
        $author = alice($this);
        $nonConfirmedAuthor = daniel($this, roles: [Roles::USER]);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        addCollaborator($story->id, $nonConfirmedAuthor->id, 'author');

        $response = $this->actingAs($nonConfirmedAuthor)
            ->get('/quotes/chapter-aggregate?chapter_id=' . $chapter->id);

        $response->assertRedirect(route('dashboard'));
    });

    it('forbids a chapter belonging to another authors story', function () {
        $author = alice($this);
        $otherAuthor = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $otherStory = publicStory('Other story', $otherAuthor->id);
        createPublishedChapter($this, $otherStory, $otherAuthor);

        $this->actingAs($otherAuthor)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id)
            ->assertForbidden();
    });

    it('ignores a forged story_id parameter', function () {
        $author = alice($this);
        $otherAuthor = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $otherStory = publicStory('Other story', $otherAuthor->id);

        $this->actingAs($otherAuthor)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id . '&story_id=' . $otherStory->id)
            ->assertForbidden();
    });

    it('forbids an unknown chapter id', function () {
        $author = alice($this);

        $this->actingAs($author)
            ->getJson('/quotes/chapter-aggregate?chapter_id=999999')
            ->assertForbidden();
    });

    it('rejects a missing chapter_id', function () {
        $author = alice($this);

        $this->actingAs($author)
            ->getJson('/quotes/chapter-aggregate')
            ->assertStatus(422);
    });

    it('rejects a non-numeric chapter_id', function () {
        $author = alice($this);

        $this->actingAs($author)
            ->getJson('/quotes/chapter-aggregate?chapter_id=abc')
            ->assertStatus(422);
    });

    it('returns no note key anywhere in the response body', function () {
        $author = alice($this);
        $reader = bob($this);
        $secondReader = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id, ['note' => 'a private note']);
        createQuote($secondReader->id, $chapter->id, $story->id, ['note' => 'another private note']);

        $response = $this->actingAs($author)
            ->getJson('/quotes/chapter-aggregate?chapter_id=' . $chapter->id);

        $response->assertOk()->assertJsonCount(2, 'items');

        expect($response->getContent())->not->toContain('note');
        expect($response->getContent())->not->toContain('private');
    });
});
