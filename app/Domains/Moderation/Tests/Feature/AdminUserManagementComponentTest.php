<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AdminUserManagementComponent', function () {
    it('displays a search field for privileged users', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $rendered = Blade::render('<x-moderation::admin-user-management-component />');

        expect($rendered)->toContain('type="text"');
        expect($rendered)->toContain('moderation::admin.user_management.search_instruction');
    });

    it('throws for guests', function () {
        Auth::logout();

        expect(fn () => Blade::render('<x-moderation::admin-user-management-component />'))
            ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    });

    it('throws for regular non-privileged users', function () {
        $user = alice($this);
        $this->actingAs($user);

        expect(fn () => Blade::render('<x-moderation::admin-user-management-component />'))
            ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    });
});
