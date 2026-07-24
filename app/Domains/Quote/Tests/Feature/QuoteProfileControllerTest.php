<?php

use App\Domains\Quote\Private\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('GET /quotes/profile/{profileSlug}', function () {
    it('owner sees their own quotes', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id, ['highlighted_text' => 'passage']);

        $profile = \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $reader->id)->firstOrFail();

        $response = $this->actingAs($reader)
            ->getJson('/quotes/profile/' . $profile->slug);

        $response->assertOk()
            ->assertJsonPath('viewer_is_owner', true)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.highlighted_text', 'passage');
    });

    it('unauthenticated user sees 403 for non-public quote book', function () {
        $reader = bob($this);
        $profile = \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $reader->id)->firstOrFail();

        $response = $this->getJson('/quotes/profile/' . $profile->slug);

        $response->assertOk()
            ->assertJsonPath('total_count', 0);
    });

    it('returns 404 for unknown profile', function () {
        $this->getJson('/quotes/profile/nonexistent-slug-99999')->assertNotFound();
    });
});
