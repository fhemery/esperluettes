<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('private');
});

describe('GET /media/library', function () {
    it('requires authentication', function () {
        $this->get('/media/library?scope=news')->assertRedirect();
    });

    it('returns originals for a scope as JSON', function () {
        Storage::disk('public')->put('news/a.jpg', 'x');
        Storage::disk('public')->put('news/a-400w.webp', 'x');
        Storage::disk('public')->put('news/a-800w.webp', 'x');

        $this->actingAs(alice($this))
            ->getJson('/media/library?scope=news')
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonPath('items.0.path', 'news/a.jpg')
            ->assertJsonPath('items.0.url', asset('storage/news/a-400w.webp'));
    });

    it('returns the original URL when an image has no variants', function () {
        Storage::disk('public')->put('chapters/7/keep.jpg', 'x');

        $this->actingAs(alice($this))
            ->getJson('/media/library?scope=chapters/7')
            ->assertOk()
            ->assertJsonPath('items.0.path', 'chapters/7/keep.jpg')
            ->assertJsonPath('items.0.url', asset('storage/chapters/7/keep.jpg'));
    });

    it('rejects an unknown scope', function () {
        $this->actingAs(alice($this))
            ->getJson('/media/library?scope=bogus')
            ->assertStatus(422);
    });

    it('rejects the retired calendar and profile scopes', function () {
        $user = alice($this);
        $this->actingAs($user)->getJson('/media/library?scope=calendar')->assertStatus(422);
        $this->actingAs($user)->getJson('/media/library?scope=profile')->assertStatus(422);
    });

    it('does not expose a private scope through the library endpoint', function () {
        Storage::disk('private')->put('secret-gift/7/gift.jpg', 'x');

        $this->actingAs(alice($this))
            ->getJson('/media/library?scope=secret-gift/7')
            ->assertStatus(422)
            ->assertJsonMissingPath('items');
    });

    it('accepts the activities scope', function () {
        Storage::disk('public')->put('activities/a.jpg', 'x');

        $this->actingAs(alice($this))
            ->getJson('/media/library?scope=activities')
            ->assertOk()
            ->assertJsonPath('items.0.path', 'activities/a.jpg');
    });
});
