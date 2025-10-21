<?php

use App\Domains\Auth\Private\Models\Role;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Role::query()->delete();
});

describe('role lookup', function () {
    it('searches roles by partial name or slug', function () {
        // Seed some roles
        Role::create(['name' => 'Administrator', 'slug' => Roles::ADMIN]);
        Role::create(['name' => 'Confirmed User', 'slug' => Roles::USER_CONFIRMED]);
        Role::create(['name' => 'User', 'slug' => Roles::USER]);

        $admin = admin($this);
        $this->actingAs($admin);

        $resp = $this->getJson('/auth/roles/lookup?q=adm');
        $resp->assertOk();
        $data = $resp->json();

        expect($data)->toHaveKey('roles');
        $slugs = array_map(fn($r) => $r['slug'], $data['roles']);
        expect($slugs)->toContain(Roles::ADMIN);
    });
});

describe('role lookup by slugs', function () {
    it('fetches roles by slugs', function () {
        Role::create(['name' => 'Administrator', 'slug' => Roles::ADMIN]);
        Role::create(['name' => 'Confirmed User', 'slug' => Roles::USER_CONFIRMED]);

        $admin = admin($this);
        $this->actingAs($admin);

        $resp = $this->getJson('/auth/roles/by-slugs?slugs=' . Roles::ADMIN . ',' . Roles::USER_CONFIRMED);
        $resp->assertOk();
        $data = $resp->json();

        expect($data)->toHaveKey('roles');
        $slugs = array_map(fn($r) => $r['slug'], $data['roles']);
        expect($slugs)->toContain(Roles::ADMIN);
        expect($slugs)->toContain(Roles::USER_CONFIRMED);
    });
});
