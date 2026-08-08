<?php

use App\Domains\News\Private\Models\News;
use App\Domains\News\Public\Events\NewsUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Pin to carousel order', function () {
    it('assigns display_order 1 and shifts existing pins when creating pinned', function () {
        $user = admin($this);
        $first = News::factory()->create([
            'title' => 'Pinned First',
            'is_pinned' => true,
            'display_order' => 1,
            'status' => 'published',
        ]);
        $second = News::factory()->create([
            'title' => 'Pinned Second',
            'is_pinned' => true,
            'display_order' => 2,
            'status' => 'published',
        ]);

        $response = $this->actingAs($user)
            ->post(route('news.admin.store'), [
                'title' => 'New Pinned',
                'slug' => 'new-pinned',
                'summary' => 'A short summary',
                'content' => '<p>News content</p>',
                'status' => 'published',
                'is_pinned' => true,
            ]);

        $response->assertRedirect(route('news.admin.index'));

        $created = News::where('slug', 'new-pinned')->first();
        expect($created)->not->toBeNull();
        expect($created->is_pinned)->toBeTrue();
        expect($created->display_order)->toBe(1);
        expect($first->fresh()->display_order)->toBe(2);
        expect($second->fresh()->display_order)->toBe(3);
    });

    it('assigns display_order 1 when pinning an existing unpinned article', function () {
        $user = admin($this);
        $pinned = News::factory()->create([
            'title' => 'Already Pinned',
            'is_pinned' => true,
            'display_order' => 1,
            'status' => 'published',
        ]);
        $unpinned = News::factory()->create([
            'title' => 'Was Unpinned',
            'slug' => 'was-unpinned',
            'summary' => 'A short summary',
            'content' => '<p>News content</p>',
            'is_pinned' => false,
            'display_order' => null,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->put(route('news.admin.update', $unpinned), [
                'title' => 'Was Unpinned',
                'slug' => 'was-unpinned',
                'summary' => 'A short summary',
                'content' => '<p>News content</p>',
                'status' => 'draft',
                'is_pinned' => true,
            ]);

        $response->assertRedirect(route('news.admin.index'));

        expect($unpinned->fresh()->display_order)->toBe(1);
        expect($pinned->fresh()->display_order)->toBe(2);
    });

    it('assigns display_order 1 when pinning into an empty carousel', function () {
        $user = admin($this);

        $response = $this->actingAs($user)
            ->post(route('news.admin.store'), [
                'title' => 'Only Pin',
                'slug' => 'only-pin',
                'summary' => 'A short summary',
                'content' => '<p>News content</p>',
                'status' => 'draft',
                'is_pinned' => true,
            ]);

        $response->assertRedirect(route('news.admin.index'));

        $created = News::where('slug', 'only-pin')->first();
        expect($created->display_order)->toBe(1);
    });

    it('does not reshuffle when saving an already pinned article', function () {
        $user = admin($this);
        $first = News::factory()->create([
            'title' => 'Order One',
            'is_pinned' => true,
            'display_order' => 1,
            'status' => 'published',
        ]);
        $second = News::factory()->create([
            'title' => 'Order Two',
            'slug' => 'order-two',
            'summary' => 'A short summary',
            'content' => '<p>News content</p>',
            'is_pinned' => true,
            'display_order' => 2,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->put(route('news.admin.update', $second), [
                'title' => 'Order Two',
                'slug' => 'order-two',
                'summary' => 'Updated summary',
                'content' => '<p>Updated content</p>',
                'status' => 'draft',
                'is_pinned' => true,
            ]);

        $response->assertRedirect(route('news.admin.index'));

        expect($second->fresh()->display_order)->toBe(2);
        expect($first->fresh()->display_order)->toBe(1);
    });

    it('does not emit NewsUpdated for sibling rows shifted by a new pin', function () {
        $user = admin($this);
        News::factory()->create([
            'title' => 'Sibling A',
            'is_pinned' => true,
            'display_order' => 1,
            'status' => 'published',
        ]);
        News::factory()->create([
            'title' => 'Sibling B',
            'is_pinned' => true,
            'display_order' => 2,
            'status' => 'published',
        ]);

        $before = countEvents(NewsUpdated::name());

        $this->actingAs($user)
            ->post(route('news.admin.store'), [
                'title' => 'New First',
                'slug' => 'new-first',
                'summary' => 'A short summary',
                'content' => '<p>News content</p>',
                'status' => 'published',
                'is_pinned' => true,
            ])
            ->assertRedirect(route('news.admin.index'));

        // Create path does not emit NewsUpdated; sibling increments must not either.
        expect(countEvents(NewsUpdated::name()))->toBe($before);
    });
});
