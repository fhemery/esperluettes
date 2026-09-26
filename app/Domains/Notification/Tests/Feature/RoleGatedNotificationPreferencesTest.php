<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Notification\Public\Contracts\NotificationChannelDefinition;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Notification\Tests\Fixtures\StaffOnlyTestNotificationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

const STAFF_ONLY_TYPE = 'test.staff.notification';

beforeEach(function () {
    app()->instance(NotificationChannelRegistry::class, new NotificationChannelRegistry());

    $factory = app(NotificationFactory::class);
    $factory->registerGroup('test-staff', 999, 'test::staff.group');
    $factory->register(
        type: StaffOnlyTestNotificationContent::type(),
        class: StaffOnlyTestNotificationContent::class,
        groupId: 'test-staff',
        nameKey: 'test::staff.type',
        visibleToRoles: [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN],
    );
});

afterEach(function () {
    clearSettingsRegistry();
});

function registerStaffTestOptInChannel(): void
{
    app(NotificationChannelRegistry::class)->register(new NotificationChannelDefinition(
        id: 'staff_optin',
        nameTranslationKey: 'test::channel',
        defaultEnabled: false,
        sortOrder: 99,
        deliveryCallback: fn ($dto, $ids) => null,
    ));
}

function staffPrefRow(int $userId, string $channel = 'website'): ?object
{
    return DB::table('notification_preferences')
        ->where('user_id', $userId)
        ->where('type', STAFF_ONLY_TYPE)
        ->where('channel', $channel)
        ->first();
}

describe('Role-gated notification preferences', function () {
    it('hides a visibleToRoles type from the settings tab for a confirmed non-staff user', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk()
            ->assertDontSee(STAFF_ONLY_TYPE)
            // the staff-only group has no visible type left, so its header is skipped too
            ->assertDontSee('test::staff.group');
    });

    it('shows a visibleToRoles type on the settings tab for moderator, admin, and tech-admin', function () {
        $staffRoles = [
            [Roles::MODERATOR, Roles::USER_CONFIRMED],
            [Roles::ADMIN, Roles::USER_CONFIRMED],
            [Roles::TECH_ADMIN, Roles::USER_CONFIRMED],
        ];

        foreach ($staffRoles as $index => $roles) {
            $user = registerUserThroughForm($this, [
                'name' => 'Staff '.$index,
                'email' => 'staff'.$index.'@example.com',
            ], true, $roles);

            $this->actingAs($user)
                ->get(route('settings.tab', ['tab' => 'notification']))
                ->assertOk()
                ->assertSee('name="prefs['.STAFF_ONLY_TYPE.'][website]"', false);
        }
    });

    it('returns 404 on PUT of a staff-only type by a non-staff user and writes no preference row', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        $this->actingAs($user)
            ->putJson(route('notification.preferences.update', ['type' => STAFF_ONLY_TYPE]), [
                'channel' => 'website',
                'enabled' => false,
            ])
            ->assertNotFound();

        expect(staffPrefRow($user->id))->toBeNull();
    });

    it('persists a staff-only type preference when a moderator PUTs a channel toggle', function () {
        $moderator = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);

        $this->actingAs($moderator)
            ->putJson(route('notification.preferences.update', ['type' => STAFF_ONLY_TYPE]), [
                'channel' => 'website',
                'enabled' => false,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $row = staffPrefRow($moderator->id);
        expect($row)->not->toBeNull();
        expect((bool) $row->enabled)->toBeFalse();
    });

    // Enable-all on an opt-in (default-off) channel is what writes rows under sparse storage.
    it('skips staff-only types on bulk enable-all for a non-staff user', function () {
        registerStaffTestOptInChannel();
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        foreach (['all', 'test-staff'] as $scope) {
            $this->actingAs($user)
                ->putJson(route('notification.preferences.bulk'), [
                    'channel' => 'staff_optin',
                    'enabled' => true,
                    'scope' => $scope,
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        }

        expect(staffPrefRow($user->id, 'staff_optin'))->toBeNull();
    });

    it('includes staff-only types on bulk enable-all for a staff user', function () {
        registerStaffTestOptInChannel();
        $moderator = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);

        $this->actingAs($moderator)
            ->putJson(route('notification.preferences.bulk'), [
                'channel' => 'staff_optin',
                'enabled' => true,
                'scope' => 'all',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $row = staffPrefRow($moderator->id, 'staff_optin');
        expect($row)->not->toBeNull();
        expect((bool) $row->enabled)->toBeTrue();
    });

    it('ignores staff-only keys in a full-form save by a non-staff user', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        $this->actingAs($user)
            ->post(route('notification.preferences.save'), [
                'prefs' => [STAFF_ONLY_TYPE => ['website' => '0']],
            ])
            ->assertRedirect();

        expect(staffPrefRow($user->id))->toBeNull();
    });
});
