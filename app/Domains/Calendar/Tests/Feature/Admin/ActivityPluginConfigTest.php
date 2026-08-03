<?php

use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Tests\Support\TestActivityRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

const PLUGIN_TYPE_KEY = 'with-config';

/**
 * Register an activity type that declares a config component and one rule.
 * The returned instance is the very one the container hands to the controller,
 * so its recorded persistConfig() calls can be asserted on.
 */
function registerConfigurableActivityType(): TestActivityRegistration
{
    $registration = new TestActivityRegistration(
        'calendar::test-fake-empty',
        'calendar::test-fake-config',
        ['plugin_label' => ['required', 'string', 'max:5']],
    );

    app(CalendarRegistry::class)->register(PLUGIN_TYPE_KEY, $registration);

    return $registration;
}

describe('Activity plugin config contract', function () {

    describe('rendering', function () {
        it('renders the config component on create when the type declares one', function () {
            registerConfigurableActivityType();

            $this->actingAs(admin($this))
                ->get(route('calendar.admin.activities.create'))
                ->assertOk()
                ->assertSee('FAKE CONFIG PANEL');
        });

        it('renders no config component for a type that returns null', function () {
            registerFakeActivityType(app(CalendarRegistry::class));

            $this->actingAs(admin($this))
                ->get(route('calendar.admin.activities.create'))
                ->assertOk()
                ->assertDontSee('FAKE CONFIG PANEL');
        });

        it('renders the config component on the edit form of that type', function () {
            registerConfigurableActivityType();
            $admin = admin($this);
            $this->actingAs($admin);
            $id = createActivity($this, ['activity_type' => PLUGIN_TYPE_KEY], $admin->id);

            $this->get(route('calendar.admin.activities.edit', Activity::findOrFail($id)))
                ->assertOk()
                ->assertSee('FAKE CONFIG PANEL');
        });

        it('renders no config component on the edit form of a type without one', function () {
            registerConfigurableActivityType();
            $admin = admin($this);
            $this->actingAs($admin);
            $id = createActivity($this, [], $admin->id);

            $this->get(route('calendar.admin.activities.edit', Activity::findOrFail($id)))
                ->assertOk()
                ->assertDontSee('FAKE CONFIG PANEL');
        });
    });

    describe('store', function () {
        it('enforces the plugin rules and creates no activity when they fail', function () {
            registerConfigurableActivityType();

            $this->actingAs(admin($this))
                ->post(route('calendar.admin.activities.store'), [
                    'name' => 'Concours',
                    'activity_type' => PLUGIN_TYPE_KEY,
                    'plugin_label' => 'much too long',
                ])
                ->assertSessionHasErrors(['plugin_label']);

            expect(Activity::query()->count())->toBe(0);
        });

        it('passes the new activity id and the validated payload to persistConfig', function () {
            $registration = registerConfigurableActivityType();

            $this->actingAs(admin($this))
                ->post(route('calendar.admin.activities.store'), [
                    'name' => 'Concours',
                    'activity_type' => PLUGIN_TYPE_KEY,
                    'plugin_label' => 'abc',
                ])
                ->assertRedirect(route('calendar.admin.activities.index'));

            $activity = Activity::query()->firstOrFail();

            expect($registration->persistedCalls)->toHaveCount(1)
                ->and($registration->persistedCalls[0]['activityId'])->toBe($activity->id)
                ->and($registration->persistedCalls[0]['validated']['plugin_label'])->toBe('abc')
                ->and($registration->persistedCalls[0]['validated']['name'])->toBe('Concours');
        });

        it('rolls back the activity when persistConfig throws', function () {
            $registration = registerConfigurableActivityType();
            $registration->throwOnPersist = true;

            $this->actingAs(admin($this))->withoutExceptionHandling();

            expect(fn () => $this->post(route('calendar.admin.activities.store'), [
                'name' => 'Concours',
                'activity_type' => PLUGIN_TYPE_KEY,
                'plugin_label' => 'abc',
            ]))->toThrow(RuntimeException::class);

            expect(Activity::query()->count())->toBe(0);
        });

        it('leaves a type without plugin rules untouched', function () {
            registerFakeActivityType(app(CalendarRegistry::class));

            $this->actingAs(admin($this))
                ->post(route('calendar.admin.activities.store'), [
                    'name' => 'Sans config',
                    'activity_type' => 'fake',
                ])
                ->assertRedirect(route('calendar.admin.activities.index'));

            $this->assertDatabaseHas('calendar_activities', ['name' => 'Sans config']);
        });
    });

    describe('update', function () {
        it('enforces the plugin rules using the stored activity type', function () {
            registerConfigurableActivityType();
            $admin = admin($this);
            $this->actingAs($admin);
            $id = createActivity($this, ['activity_type' => PLUGIN_TYPE_KEY], $admin->id);

            $this->put(route('calendar.admin.activities.update', Activity::findOrFail($id)), [
                'name' => 'Nouveau nom',
            ])->assertSessionHasErrors(['plugin_label']);

            $this->assertDatabaseMissing('calendar_activities', ['id' => $id, 'name' => 'Nouveau nom']);
        });

        it('persists the plugin config with the existing activity id', function () {
            $registration = registerConfigurableActivityType();
            $admin = admin($this);
            $this->actingAs($admin);
            $id = createActivity($this, ['activity_type' => PLUGIN_TYPE_KEY], $admin->id);

            $this->put(route('calendar.admin.activities.update', Activity::findOrFail($id)), [
                'name' => 'Nouveau nom',
                'plugin_label' => 'xyz',
            ])->assertRedirect(route('calendar.admin.activities.index'));

            expect($registration->persistedCalls)->toHaveCount(1)
                ->and($registration->persistedCalls[0]['activityId'])->toBe($id)
                ->and($registration->persistedCalls[0]['validated']['plugin_label'])->toBe('xyz');
        });

        it('rolls back the update when persistConfig throws', function () {
            $registration = registerConfigurableActivityType();
            $admin = admin($this);
            $this->actingAs($admin);
            $id = createActivity($this, ['activity_type' => PLUGIN_TYPE_KEY, 'name' => 'Ancien nom'], $admin->id);
            $registration->throwOnPersist = true;

            $this->withoutExceptionHandling();

            expect(fn () => $this->put(route('calendar.admin.activities.update', Activity::findOrFail($id)), [
                'name' => 'Nouveau nom',
                'plugin_label' => 'xyz',
            ]))->toThrow(RuntimeException::class);

            $this->assertDatabaseHas('calendar_activities', ['id' => $id, 'name' => 'Ancien nom']);
        });
    });
});
