<?php

use App\Domains\Auth\Tests\helpers as AuthTestHelpers; // for functions like admin(), alice()
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('profile lookup', function () {
    it('returns profiles for search with avatar_url and total', function () {
        $admin = admin($this);
        alice($this, ['name' => 'Jane Alpha']);
        bob($this, ['name' => 'Janet Beta']);
        carol($this, ['name' => 'Zeta']);

        $this->actingAs($admin);

        $resp = $this->getJson('/profile/lookup?q=Jan&limit=25');
        $resp->assertOk();
        $data = $resp->json();

        expect($data)->toHaveKeys(['profiles', 'total']);
        expect($data['profiles'])->toBeArray();
        // Should include at least Jane and Janet
        $names = array_map(fn($p) => $p['display_name'], $data['profiles']);
        expect(collect($names)->contains(fn($n) => str_contains($n, 'Jane')))->toBeTrue();
        expect(collect($names)->contains(fn($n) => str_contains($n, 'Janet')))->toBeTrue();

        // Each item has id, display_name, avatar_url
        $first = $data['profiles'][0] ?? null;
        expect($first)->not()->toBeNull();
        expect($first)->toHaveKeys(['id', 'display_name', 'avatar_url']);
    });
});

describe('profile lookup by ids', function () {
    it('returns profiles by ids for preload', function () {
        $admin = admin($this);
        $u1 = alice($this, ['name' => 'Foo User']);
        $u2 = bob($this, ['name' => 'Bar User']);

        $this->actingAs($admin);

        $resp = $this->getJson('/profile/lookup/by-ids?ids=' . implode(',', [$u1->id, $u2->id]));
        $resp->assertOk();
        $data = $resp->json();

        expect($data)->toHaveKey('profiles');
        $ids = array_map(fn($p) => $p['id'], $data['profiles']);
        expect($ids)->toContain($u1->id);
        expect($ids)->toContain($u2->id);
    });
});
