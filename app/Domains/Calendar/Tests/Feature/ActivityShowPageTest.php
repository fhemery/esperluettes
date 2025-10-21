<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Models\Activity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Route under test: GET /activities/{slug}
 */

describe('Activity detail page', function () {
    it('redirects guests to login', function () {
        // Visible activity (preview state), restricted to confirmed users
        $admin = admin($this);
        $this->actingAs($admin);
        $id = createActivity($this, [
            'name' => 'Guest Redirect Activity',
            'role_restrictions' => [Roles::USER_CONFIRMED],
            'preview_starts_at' => Carbon::now()->subDay(),
            'active_starts_at' => Carbon::now()->addDays(5),
        ], $admin->id);
        Auth::logout();

        /** @var Activity $activity */
        $activity = Activity::query()->findOrFail($id);

        $response = $this->get('/activities/' . $activity->slug);
        $response->assertRedirect('/login');
    });

    it('returns 404 for user without required role', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $id = createActivity($this, [
            'name' => 'Moderator Only Detail',
            'role_restrictions' => [Roles::MODERATOR],
            'preview_starts_at' => Carbon::now()->subDay(),
            'active_starts_at' => Carbon::now()->addDays(5),
        ], $admin->id);

        /** @var Activity $activity */
        $activity = Activity::query()->findOrFail($id);

        // Regular confirmed user, not moderator
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $this->get('/activities/' . $activity->slug)->assertNotFound();
    });

    it('returns 404 for draft activity even with allowed role', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $id = createActivity($this, [
            'name' => 'Draft Detail',
            'role_restrictions' => [Roles::ADMIN],
            'preview_starts_at' => null, // draft
        ], $admin->id);

        $activity = Activity::query()->findOrFail($id);
        $this->get('/activities/' . $activity->slug)->assertNotFound();
    });

    it('returns 404 for archived activity even with allowed role', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $id = createActivity($this, [
            'name' => 'Archived Detail',
            'role_restrictions' => [Roles::ADMIN],
            'preview_starts_at' => Carbon::now()->subDays(30),
            'active_starts_at' => Carbon::now()->subDays(20),
            'active_ends_at' => Carbon::now()->subDays(10),
            'archived_at' => Carbon::now()->subDay(),
        ], $admin->id);

        $activity = Activity::query()->findOrFail($id);
        $this->get('/activities/' . $activity->slug)->assertNotFound();
    });

    it('shows preview activity for allowed role', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $id = createActivity($this, [
            'name' => 'Preview Detail',
            'role_restrictions' => [Roles::USER_CONFIRMED],
            'preview_starts_at' => Carbon::now()->subDay(),
            'active_starts_at' => Carbon::now()->addDays(5),
        ], $admin->id);

        $activity = Activity::query()->findOrFail($id);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $response = $this->get('/activities/' . $activity->slug);
        $response->assertOk();
        $response->assertSee('Preview Detail');
    });

    it('shows active activity for allowed role', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $id = createActivity($this, [
            'name' => 'Active Detail',
            'role_restrictions' => [Roles::USER_CONFIRMED],
            'preview_starts_at' => Carbon::now()->subDays(10),
            'active_starts_at' => Carbon::now()->subDays(2),
            'active_ends_at' => Carbon::now()->addDays(10),
        ], $admin->id);

        $activity = Activity::query()->findOrFail($id);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $response = $this->get('/activities/' . $activity->slug);
        $response->assertOk();
        $response->assertSee('Active Detail');
    });

    it('shows ended activity for allowed role', function () {
        $admin = admin($this);
        $this->actingAs($admin);
        $id = createActivity($this, [
            'name' => 'Ended Detail',
            'role_restrictions' => [Roles::USER_CONFIRMED],
            'preview_starts_at' => Carbon::now()->subDays(20),
            'active_starts_at' => Carbon::now()->subDays(15),
            'active_ends_at' => Carbon::now()->subDays(5),
            'archived_at' => null,
        ], $admin->id);

        $activity = Activity::query()->findOrFail($id);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $response = $this->get('/activities/' . $activity->slug);
        $response->assertOk();
        $response->assertSee('Ended Detail');
    });
});
