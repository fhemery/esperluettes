<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Quote\Private\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Setup below references collaborating domains via fully-qualified inline calls
// (kept out of `use` imports so the QuoteTests deptrac layer stays clean — same
// pattern as the profile-model lookups already used in this file).

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

    it('unauthenticated user sees an empty book', function () {
        $reader = bob($this);
        $profile = \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $reader->id)->firstOrFail();

        $response = $this->getJson('/quotes/profile/' . $profile->slug);

        $response->assertOk()
            ->assertJsonPath('total_count', 0);
    });

    it('returns 404 for unknown profile', function () {
        $this->getJson('/quotes/profile/nonexistent-slug-99999')->assertNotFound();
    });

    it('hides the private note from a confirmed non-owner viewing a visible book', function () {
        $author = alice($this);
        $reader = bob($this);
        $viewer = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id, [
            'highlighted_text' => 'passage',
            'note' => '<strong>secret note</strong>',
        ]);

        $response = $this->actingAs($viewer)
            ->getJson('/quotes/profile/' . \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $reader->id)->firstOrFail()->slug);

        $response->assertOk()
            ->assertJsonPath('viewer_is_owner', false)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.note', null);

        expect($response->getContent())->not->toContain('secret note');
    });

    it('excludes inaccessible-chapter quotes and reflects it in the total', function () {
        $author = alice($this);
        $reader = bob($this);
        $viewer = carol($this);

        $publicStoryModel = publicStory('Public Story', $author->id);
        $publicChapter = createPublishedChapter($this, $publicStoryModel, $author);

        $privateStoryModel = privateStory('Private Story', $author->id);
        $privateChapter = createPublishedChapter($this, $privateStoryModel, $author);

        createQuote($reader->id, $publicChapter->id, $publicStoryModel->id, ['highlighted_text' => 'visible']);
        createQuote($reader->id, $privateChapter->id, $privateStoryModel->id, ['highlighted_text' => 'hidden']);

        $response = $this->actingAs($viewer)
            ->getJson('/quotes/profile/' . \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $reader->id)->firstOrFail()->slug);

        $response->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('total_count', 1)
            ->assertJsonPath('items.0.highlighted_text', 'visible');
    });

    it('does not show the book to a confirmed viewer when the owner hid the tab', function () {
        $author = alice($this);
        $reader = bob($this);
        $viewer = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id, ['highlighted_text' => 'passage']);
        app(\App\Domains\Settings\Public\Api\SettingsPublicApi::class)->setValue(
            $reader->id,
            \App\Domains\Quote\Public\Providers\QuoteServiceProvider::TAB_PROFILE,
            \App\Domains\Quote\Public\Providers\QuoteServiceProvider::KEY_HIDE_QUOTES_TAB,
            true,
        );

        $response = $this->actingAs($viewer)
            ->getJson('/quotes/profile/' . \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $reader->id)->firstOrFail()->slug);

        $response->assertOk()->assertJsonPath('total_count', 0);
    });

    it('shows the book to a confirmed viewer by default', function () {
        $author = alice($this);
        $reader = bob($this);
        $viewer = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id, ['highlighted_text' => 'passage']);

        $response = $this->actingAs($viewer)
            ->getJson('/quotes/profile/' . \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $reader->id)->firstOrFail()->slug);

        $response->assertOk()
            ->assertJsonPath('viewer_is_owner', false)
            ->assertJsonCount(1, 'items');
    });

    it('does not show a visible book to an unconfirmed viewer', function () {
        $author = alice($this);
        $reader = bob($this);
        $unconfirmed = carol($this, roles: [Roles::USER]);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id, ['highlighted_text' => 'passage']);

        $response = $this->actingAs($unconfirmed)
            ->getJson('/quotes/profile/' . \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $reader->id)->firstOrFail()->slug);

        $response->assertOk()->assertJsonPath('total_count', 0);
    });
});

describe('Quotes profile tab — visibility indicator', function () {
    it('shows the visibility icon when owner views their book (visible by default)', function () {
        $owner = alice($this);
        $slug = \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $owner->id)->firstOrFail()->slug;

        $html = $this->actingAs($owner)
            ->get(route('profile.show.quotes', $slug))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('visibility')
            ->and($html)->not->toContain('visibility_off');
    });

    it('shows the visibility_off icon when the owner hid the tab', function () {
        $owner = alice($this);
        app(\App\Domains\Settings\Public\Api\SettingsPublicApi::class)->setValue(
            $owner->id,
            \App\Domains\Quote\Public\Providers\QuoteServiceProvider::TAB_PROFILE,
            \App\Domains\Quote\Public\Providers\QuoteServiceProvider::KEY_HIDE_QUOTES_TAB,
            true,
        );
        $slug = \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $owner->id)->firstOrFail()->slug;

        $this->actingAs($owner)
            ->get(route('profile.show.quotes', $slug))
            ->assertOk()
            ->assertSee('visibility_off');
    });

    it('does not show the visibility indicator to other users', function () {
        $owner = alice($this);
        $viewer = bob($this);
        $slug = \App\Domains\Profile\Private\Models\Profile::query()->where('user_id', $owner->id)->firstOrFail()->slug;

        $this->actingAs($viewer)
            ->get(route('profile.show.quotes', $slug))
            ->assertOk()
            ->assertDontSee('data-quote-visibility-indicator', false);
    });
});
