<?php

declare(strict_types=1);

use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ActivityListComponent', function () {
    beforeEach(function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        registerFakeActivityType($registry);
    });

    describe('Activity access', function () {
        it('filters out activities the user is not allowed to see (role restrictions)', function () {
            // Regular confirmed user
            $user = alice($this, roles: [\App\Domains\Auth\Public\Api\Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            // Create an activity restricted to MODERATOR only
            $adminUser = admin($this);
            $this->actingAs($adminUser);
            createActivity($this, [
                'name' => 'Moderator Only Activity',
                'role_restrictions' => [\App\Domains\Auth\Public\Api\Roles::MODERATOR],
                'preview_starts_at' => Carbon::now()->subDay(),
                'active_starts_at' => Carbon::now()->addDays(5),
            ], $adminUser->id);

            // Switch back to regular user (not moderator)
            $this->actingAs($user);

            $html = Blade::render('<x-calendar::activity-list-component />');
            expect($html)->not->toContain('Moderator Only Activity');
        });
    });

    it('renders empty state in Blade when no activities exist', function () {
        $html = Blade::render('<x-calendar::activity-list-component />');
        expect($html)
            ->toContain('event_busy')
            ->and($html)->toContain(__('calendar::activity.list.no_activities'));
    });

    describe('included activities', function () {
        it('excludes draft and archived activities from the list', function () {
            $adminUser = admin($this);
            $this->actingAs($adminUser);

            // Create a draft activity (no preview_starts_at)
            createActivity($this, [
                'name' => 'Draft Activity',
                'preview_starts_at' => null,
            ], $adminUser->id);

            createActivity($this, [
                'name' => 'Archived Activity',
                'preview_starts_at' => Carbon::now()->subDays(30),
                'active_starts_at' => Carbon::now()->subDays(20),
                'active_ends_at' => Carbon::now()->subDays(10),
                'archived_at' => Carbon::now()->subDays(5),
            ], $adminUser->id);

            $html = Blade::render('<x-calendar::activity-list-component />');
            expect($html)->not->toContain('Draft Activity');
            expect($html)->not->toContain('Archived Activity');
        });

        it('includes preview activities', function () {
            $adminUser = admin($this);
            $this->actingAs($adminUser);

            createActivity($this, [
                'name' => 'Preview Activity',
                'preview_starts_at' => Carbon::now()->subDay(),
                'active_starts_at' => Carbon::now()->addDays(5),
            ], $adminUser->id);

            $html = Blade::render('<x-calendar::activity-list-component />');
            expect($html)->toContain('Preview Activity');
        });

        it('sorts activities by state priority: active first, then preview, then ended', function () {
            $adminUser = admin($this);
            $this->actingAs($adminUser);

            // Create in mixed order
            createActivity($this, [
                'name' => 'Ended Activity',
                'preview_starts_at' => Carbon::now()->subDays(20),
                'active_starts_at' => Carbon::now()->subDays(15),
                'active_ends_at' => Carbon::now()->subDays(5),
            ], $adminUser->id);

            createActivity($this, [
                'name' => 'Active Activity',
                'preview_starts_at' => Carbon::now()->subDays(10),
                'active_starts_at' => Carbon::now()->subDays(5),
                'active_ends_at' => Carbon::now()->addDays(5),
            ], $adminUser->id);

            createActivity($this, [
                'name' => 'Preview Activity',
                'preview_starts_at' => Carbon::now()->subDay(),
                'active_starts_at' => Carbon::now()->addDays(5),
            ], $adminUser->id);

            $html = Blade::render('<x-calendar::activity-list-component />');
            $posActive = strpos($html, 'Active Activity');
            $posPreview = strpos($html, 'Preview Activity');
            $posEnded = strpos($html, 'Ended Activity');
            expect($posActive)->toBeInt()->and($posPreview)->toBeInt()->and($posEnded)->toBeInt();
            expect($posActive < $posPreview)->toBeTrue();
            expect($posPreview < $posEnded)->toBeTrue();
        });

        it('sorts active activities by end date ascending', function () {
            $adminUser = admin($this);
            $this->actingAs($adminUser);

            createActivity($this, [
                'name' => 'Active 1 - Ends Later',
                'preview_starts_at' => Carbon::now()->subDays(10),
                'active_starts_at' => Carbon::now()->subDays(5),
                'active_ends_at' => Carbon::now()->addDays(10),
            ], $adminUser->id);

            createActivity($this, [
                'name' => 'Active 2 - Ends Sooner',
                'preview_starts_at' => Carbon::now()->subDays(10),
                'active_starts_at' => Carbon::now()->subDays(5),
                'active_ends_at' => Carbon::now()->addDays(3),
            ], $adminUser->id);

            $html = Blade::render('<x-calendar::activity-list-component />');
            $posSooner = strpos($html, 'Active 2 - Ends Sooner');
            $posLater = strpos($html, 'Active 1 - Ends Later');
            expect($posSooner)->toBeInt()->and($posLater)->toBeInt();
            expect($posSooner < $posLater)->toBeTrue();
        });

        it('sorts preview activities by start date ascending', function () {
            $adminUser = admin($this);
            $this->actingAs($adminUser);

            createActivity($this, [
                'name' => 'Preview 1 - Starts Later',
                'preview_starts_at' => Carbon::now()->subDay(),
                'active_starts_at' => Carbon::now()->addDays(10),
            ], $adminUser->id);

            createActivity($this, [
                'name' => 'Preview 2 - Starts Sooner',
                'preview_starts_at' => Carbon::now()->subDay(),
                'active_starts_at' => Carbon::now()->addDays(3),
            ], $adminUser->id);

            $html = Blade::render('<x-calendar::activity-list-component />');
            $posSooner = strpos($html, 'Preview 2 - Starts Sooner');
            $posLater = strpos($html, 'Preview 1 - Starts Later');
            expect($posSooner)->toBeInt()->and($posLater)->toBeInt();
            expect($posSooner < $posLater)->toBeTrue();
        });
    });
});
