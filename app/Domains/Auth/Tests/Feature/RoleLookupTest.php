<?php

use App\Domains\Auth\Private\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Role::query()->delete();
});

describe('role lookup', function () {
    it('searches roles by partial name or slug', function () {
        // Seed some roles
        Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        Role::create(['name' => 'Confirmed User', 'slug' => 'user-confirmed']);
        Role::create(['name' => 'User', 'slug' => 'user']);

        $admin = admin($this);
        $this->actingAs($admin);

        $resp = $this->getJson('/auth/roles/lookup?q=adm');
        $resp->assertOk();
        $data = $resp->json();

        expect($data)->toHaveKey('roles');
        $slugs = array_map(fn($r) => $r['slug'], $data['roles']);
        expect($slugs)->toContain('admin');
    });
});

describe('role lookup by slugs', function () {
    it('fetches roles by slugs', function () {
        Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        Role::create(['name' => 'Confirmed User', 'slug' => 'user-confirmed']);

        $admin = admin($this);
        $this->actingAs($admin);

        $resp = $this->getJson('/auth/roles/by-slugs?slugs=admin,user-confirmed');
        $resp->assertOk();
        $data = $resp->json();

        expect($data)->toHaveKey('roles');
        $slugs = array_map(fn($r) => $r['slug'], $data['roles']);
        expect($slugs)->toContain('admin');
        expect($slugs)->toContain('user-confirmed');
    });
});
