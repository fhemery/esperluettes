<?php

use App\Domains\Notification\Public\Contracts\NotificationChannelDefinition;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Constants for real registered types
const NORMAL_TYPE = 'story.chapter.root_comment';  // not forced, not hidden
const FORCED_TYPE = 'auth.promotion.accepted';      // forcedOnWebsite = true
const HIDDEN_TYPE = 'story.chapter.comment';        // hideInSettings = true

beforeEach(function () {
    // Fresh channel registry per test for isolation
    app()->instance(NotificationChannelRegistry::class, new NotificationChannelRegistry());
});

afterEach(function () {
    // Static SettingsRegistryService state must be cleared so the next test's app boot
    // can re-register the notification tab without a "already registered" exception.
    clearSettingsRegistry();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function registerActiveChannel(string $id = 'test_active'): void
{
    app(NotificationChannelRegistry::class)->register(new NotificationChannelDefinition(
        id: $id,
        nameTranslationKey: 'test::channel',
        defaultEnabled: false,
        sortOrder: 99,
        deliveryCallback: fn($n, $ids) => null,
    ));
}

function registerInactiveChannel(string $id = 'test_inactive'): void
{
    app(NotificationChannelRegistry::class)->register(new NotificationChannelDefinition(
        id: $id,
        nameTranslationKey: 'test::channel',
        defaultEnabled: false,
        sortOrder: 99,
        deliveryCallback: fn($n, $ids) => null,
        featureCheck: fn() => false, // inactive channel
    ));
}

function prefRow(int $userId, string $type, string $channel): ?object
{
    return DB::table('notification_preferences')
        ->where('user_id', $userId)
        ->where('type', $type)
        ->where('channel', $channel)
        ->first();
}

/**
 * Assert that the toggle for type+channel is in the checked state.
 * Uses a regex to locate the <input> element by name and verify 'checked' appears before its closing >.
 */
function assertChecked(\Illuminate\Testing\TestResponse $response, string $type, string $channel): void
{
    $pattern = '/' . preg_quote('name="prefs['.$type.']['.$channel.']"', '/') . '[^>]*checked[^>]*>/';
    \PHPUnit\Framework\Assert::assertMatchesRegularExpression(
        $pattern,
        $response->getContent(),
        "Expected toggle prefs[{$type}][{$channel}] to be checked"
    );
}

/**
 * Assert that the toggle for type+channel is in the unchecked state.
 */
function assertUnchecked(\Illuminate\Testing\TestResponse $response, string $type, string $channel): void
{
    $pattern = '/' . preg_quote('name="prefs['.$type.']['.$channel.']"', '/') . '[^>]*checked[^>]*>/';
    \PHPUnit\Framework\Assert::assertDoesNotMatchRegularExpression(
        $pattern,
        $response->getContent(),
        "Expected toggle prefs[{$type}][{$channel}] to NOT be checked"
    );
}

// ---------------------------------------------------------------------------
// Page rendering
// ---------------------------------------------------------------------------

describe('Notification preferences — page rendering', function () {
    it('renders the notification tab and includes the save form', function () {
        $user = alice($this);

        $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk()
            ->assertSee('notifications::settings.info_future_only') // raw key — no locale
            ->assertSee('action="'.route('notification.preferences.save').'"', false); // form action URL
    });

    it('requires authentication to access the settings tab', function () {
        $this->get(route('settings.index', ['tab' => 'notification']))
            ->assertRedirect(route('login'));
    });

    it('renders website checkboxes as checked by default for a fresh user', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk();

        // No stored preference → website default is ON
        assertChecked($response, NORMAL_TYPE, 'website');
    });

    it('renders external channel checkboxes as unchecked by default', function () {
        registerActiveChannel('discord');
        $user = alice($this);

        $response = $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk();

        // No stored preference → external channel default is OFF
        assertUnchecked($response, NORMAL_TYPE, 'discord');
    });

    it('renders forced website type as checked and disabled', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk();

        // Forced type toggle must be checked and carry the disabled attribute
        $content = $response->getContent();
        $quotedName = preg_quote('name="prefs['.FORCED_TYPE.'][website]"', '/');
        \PHPUnit\Framework\Assert::assertMatchesRegularExpression(
            '/' . $quotedName . '[^>]*checked[^>]*>/',
            $content,
            'Expected forced type toggle to be checked'
        );
        \PHPUnit\Framework\Assert::assertMatchesRegularExpression(
            '/' . $quotedName . '[^>]*disabled[^>]*>/',
            $content,
            'Expected forced type toggle to be disabled'
        );
    });

    it('hides types flagged hideInSettings from the form', function () {
        $user = alice($this);

        $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk()
            ->assertDontSee(HIDDEN_TYPE);
    });

    it('shows a column header for each active external channel', function () {
        registerActiveChannel('discord');
        $user = alice($this);

        $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk()
            ->assertSee('discord', false);
    });

    it('reflects a stored opt-out as unchecked on page load', function () {
        $user = alice($this);

        // Directly seed a disabled preference
        DB::table('notification_preferences')->insert([
            'user_id' => $user->id,
            'type'    => NORMAL_TYPE,
            'channel' => 'website',
            'enabled' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk();

        assertUnchecked($response, NORMAL_TYPE, 'website');
    });

    it('reflects a stored opt-in on an external channel as checked on page load', function () {
        registerActiveChannel('discord');
        $user = alice($this);

        DB::table('notification_preferences')->insert([
            'user_id' => $user->id,
            'type'    => NORMAL_TYPE,
            'channel' => 'discord',
            'enabled' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk();

        assertChecked($response, NORMAL_TYPE, 'discord');
    });
});

// ---------------------------------------------------------------------------
// Form save
// ---------------------------------------------------------------------------

describe('Notification preferences — form save', function () {
    it('requires authentication', function () {
        $this->post(route('notification.preferences.save'), [
            'prefs' => [NORMAL_TYPE => ['website' => '1']],
        ])->assertRedirect(route('login'));
    });

    it('redirects to the notification settings tab after saving', function () {
        $user = alice($this);

        $this->actingAs($user)
            ->post(route('notification.preferences.save'), [])
            ->assertRedirect(route('settings.index', ['tab' => 'notification']));
    });

    it('stores an opt-out for the website channel (differs from default ON)', function () {
        $user = alice($this);

        $this->actingAs($user)
            ->post(route('notification.preferences.save'), [
                'prefs' => [NORMAL_TYPE => ['website' => '0']],
            ]);

        $row = prefRow($user->id, NORMAL_TYPE, 'website');
        expect($row)->not->toBeNull();
        expect((bool) $row->enabled)->toBeFalse();
    });

    it('uses sparse storage — deletes row when website restored to default ON', function () {
        $user = alice($this);

        // First create an opt-out row
        $this->actingAs($user)->post(route('notification.preferences.save'), [
            'prefs' => [NORMAL_TYPE => ['website' => '0']],
        ]);
        expect(prefRow($user->id, NORMAL_TYPE, 'website'))->not->toBeNull();

        // Restore to default (true) → row deleted
        $this->actingAs($user)->post(route('notification.preferences.save'), [
            'prefs' => [NORMAL_TYPE => ['website' => '1']],
        ]);
        expect(prefRow($user->id, NORMAL_TYPE, 'website'))->toBeNull();
    });

    it('stores an opt-in for an external channel (differs from default OFF)', function () {
        registerActiveChannel('discord');
        $user = alice($this);

        $this->actingAs($user)
            ->post(route('notification.preferences.save'), [
                'prefs' => [NORMAL_TYPE => ['discord' => '1']],
            ]);

        $row = prefRow($user->id, NORMAL_TYPE, 'discord');
        expect($row)->not->toBeNull();
        expect((bool) $row->enabled)->toBeTrue();
    });

    it('uses sparse storage — deletes row when external channel set back to default OFF', function () {
        registerActiveChannel('discord');
        $user = alice($this);

        // Create an opt-in row
        $this->actingAs($user)->post(route('notification.preferences.save'), [
            'prefs' => [NORMAL_TYPE => ['discord' => '1']],
        ]);
        expect(prefRow($user->id, NORMAL_TYPE, 'discord'))->not->toBeNull();

        // Restore to default (false) → row deleted
        $this->actingAs($user)->post(route('notification.preferences.save'), [
            'prefs' => [NORMAL_TYPE => ['discord' => '0']],
        ]);
        expect(prefRow($user->id, NORMAL_TYPE, 'discord'))->toBeNull();
    });

    it('ignores submitted values for forced website types', function () {
        $user = alice($this);

        // Even if a crafted POST includes a value for a forced type, it must be ignored
        $this->actingAs($user)
            ->post(route('notification.preferences.save'), [
                'prefs' => [FORCED_TYPE => ['website' => '0']],
            ]);

        expect(prefRow($user->id, FORCED_TYPE, 'website'))->toBeNull();
    });

    it('persists opt-out and the next page load shows the checkbox unchecked', function () {
        $user = alice($this);

        $this->actingAs($user)->post(route('notification.preferences.save'), [
            'prefs' => [NORMAL_TYPE => ['website' => '0']],
        ]);

        $response = $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk();

        assertUnchecked($response, NORMAL_TYPE, 'website');
    });

    it('persists opt-in on external channel and the next page load shows the checkbox checked', function () {
        registerActiveChannel('discord');
        $user = alice($this);

        $this->actingAs($user)->post(route('notification.preferences.save'), [
            'prefs' => [NORMAL_TYPE => ['discord' => '1']],
        ]);

        $response = $this->actingAs($user)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk();

        assertChecked($response, NORMAL_TYPE, 'discord');
    });
});
