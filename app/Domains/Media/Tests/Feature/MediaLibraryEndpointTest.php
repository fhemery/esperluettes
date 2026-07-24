<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

describe('GET /media/library', function () {
    it('requires authentication', function () {
        $this->get('/media/library?scope=news')->assertRedirect();
    });

    it('returns originals for a scope as JSON', function () {
        Storage::disk('public')->put('news/a.jpg', 'x');
        Storage::disk('public')->put('news/a-400w.webp', 'x');

        $this->actingAs(alice($this))
            ->getJson('/media/library?scope=news')
            ->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonPath('items.0.path', 'news/a.jpg');
    });

    it('rejects an unknown scope', function () {
        $this->actingAs(alice($this))
            ->getJson('/media/library?scope=bogus')
            ->assertStatus(422);
    });
});
