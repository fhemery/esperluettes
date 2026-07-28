<?php

use App\Domains\Quote\Private\Models\Quote;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('GET /quotes', function () {
    it('returns viewer quotes for a chapter', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id, ['highlighted_text' => 'Hello world']);

        $response = $this->actingAs($reader)
            ->getJson('/quotes?chapter_id=' . $chapter->id . '&story_id=' . $story->id);

        $response->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.highlighted_text', 'Hello world');
    });

    it('returns empty for chapter with no quotes', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $response = $this->actingAs($reader)
            ->getJson('/quotes?chapter_id=' . $chapter->id . '&story_id=' . $story->id);

        $response->assertOk()->assertJsonCount(0, 'items');
    });

    it('returns 422 if chapter_id missing', function () {
        $reader = bob($this);

        $this->actingAs($reader)->getJson('/quotes?story_id=1')->assertStatus(422);
    });

    it('requires authentication', function () {
        $this->getJson('/quotes?chapter_id=1&story_id=1')->assertUnauthorized();
    });
});

describe('POST /quotes', function () {
    it('confirmed user can create a quote', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $response = $this->actingAs($reader)
            ->postJson('/quotes', [
                'chapter_id' => $chapter->id,
                'story_id' => $story->id,
                'highlighted_text' => 'A memorable passage',
                'prefix' => 'some words',
                'suffix' => 'more words',
                'note' => null,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('highlighted_text', 'A memorable passage');

        $this->assertDatabaseHas('quotes', [
            'user_id' => $reader->id,
            'chapter_id' => $chapter->id,
            'story_id' => $story->id,
            'highlighted_text' => 'A memorable passage',
        ]);
    });

    it('author cannot quote their own story', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($author)
            ->postJson('/quotes', [
                'chapter_id' => $chapter->id,
                'story_id' => $story->id,
                'highlighted_text' => 'A passage',
            ])
            ->assertForbidden();

        $this->assertDatabaseEmpty('quotes');
    });

    it('beta reader can quote a story they beta-read', function () {
        $author = alice($this);
        $betaReader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        addCollaborator($story->id, $betaReader->id, 'beta-reader');

        $response = $this->actingAs($betaReader)
            ->postJson('/quotes', [
                'chapter_id' => $chapter->id,
                'story_id' => $story->id,
                'highlighted_text' => 'A beta-reader passage',
                'prefix' => 'some words',
                'suffix' => 'more words',
                'note' => null,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('highlighted_text', 'A beta-reader passage');

        $this->assertDatabaseHas('quotes', [
            'user_id' => $betaReader->id,
            'chapter_id' => $chapter->id,
            'story_id' => $story->id,
            'highlighted_text' => 'A beta-reader passage',
        ]);
    });

    it('co-author cannot quote a story they co-author', function () {
        $author = alice($this);
        $coAuthor = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        addCollaborator($story->id, $coAuthor->id, 'author');

        $this->actingAs($coAuthor)
            ->postJson('/quotes', [
                'chapter_id' => $chapter->id,
                'story_id' => $story->id,
                'highlighted_text' => 'A passage',
            ])
            ->assertForbidden();

        $this->assertDatabaseEmpty('quotes');
    });

    it('requires authentication', function () {
        $this->postJson('/quotes', ['chapter_id' => 1, 'story_id' => 1, 'highlighted_text' => 'text'])
            ->assertUnauthorized();
    });

    it('rejects highlighted_text over 500 chars', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)
            ->postJson('/quotes', [
                'chapter_id' => $chapter->id,
                'story_id' => $story->id,
                'highlighted_text' => str_repeat('x', 501),
            ])
            ->assertUnprocessable();
    });

    it('rejects highlighted_text over the configured max length', function () {
        config(['quote.highlighted_text_max_length' => 20]);

        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)
            ->postJson('/quotes', [
                'chapter_id' => $chapter->id,
                'story_id' => $story->id,
                'highlighted_text' => str_repeat('x', 21),
            ])
            ->assertUnprocessable();

        $this->assertDatabaseEmpty('quotes');
    });
});

describe('PUT /quotes/{quoteId}', function () {
    it('owner can update the note', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        $response = $this->actingAs($reader)
            ->putJson('/quotes/' . $quote->id, ['note' => '<strong>My note</strong>']);

        $response->assertOk()
            ->assertJsonPath('note', '<strong>My note</strong>');

        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'note' => '<strong>My note</strong>']);
    });

    it('non-owner cannot update the note', function () {
        $author = alice($this);
        $reader = bob($this);
        $thirdUser = alice($this, ['name' => 'charlie', 'email' => 'charlie@test.com']);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        $this->actingAs($thirdUser)
            ->putJson('/quotes/' . $quote->id, ['note' => 'Hacked'])
            ->assertForbidden();
    });

    it('requires authentication', function () {
        $this->putJson('/quotes/1', ['note' => 'test'])->assertUnauthorized();
    });
});

describe('DELETE /quotes/{quoteId}', function () {
    it('owner can delete a quote', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        $this->actingAs($reader)
            ->deleteJson('/quotes/' . $quote->id)
            ->assertNoContent();

        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);
    });

    it('non-owner cannot delete a quote', function () {
        $author = alice($this);
        $reader = bob($this);
        $thirdUser = alice($this, ['name' => 'charlie', 'email' => 'charlie@test.com']);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        $this->actingAs($thirdUser)
            ->deleteJson('/quotes/' . $quote->id)
            ->assertForbidden();

        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'deleted_at' => null]);
    });

    it('requires authentication', function () {
        $this->deleteJson('/quotes/1')->assertUnauthorized();
    });
});
