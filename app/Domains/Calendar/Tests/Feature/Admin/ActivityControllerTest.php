<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    registerFakeActivityType(app(CalendarRegistry::class));
});

describe('Activity Admin Controller', function () {

    describe('index', function () {
        it('redirects unauthenticated users to login', function () {
            $this->get(route('calendar.admin.activities.index'))
                ->assertRedirect(route('login'));
        });

        it('denies access to non-admins', function () {
            $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
                ->get(route('calendar.admin.activities.index'))
                ->assertRedirect();
        });

        it('displays the list for admins', function () {
            $activityId = createActivity($this, ['name' => 'Mon activité test']);

            $this->actingAs(admin($this))
                ->get(route('calendar.admin.activities.index'))
                ->assertOk()
                ->assertSee('Mon activité test');
        });

        it('displays the list for tech-admins', function () {
            $activityId = createActivity($this, ['name' => 'Activité tech']);

            $this->actingAs(techAdmin($this))
                ->get(route('calendar.admin.activities.index'))
                ->assertOk()
                ->assertSee('Activité tech');
        });
    });

    describe('create', function () {
        it('displays the create form for admins', function () {
            $this->actingAs(admin($this))
                ->get(route('calendar.admin.activities.create'))
                ->assertOk();
        });

        it('denies access to non-admins', function () {
            $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
                ->get(route('calendar.admin.activities.create'))
                ->assertRedirect();
        });
    });

    describe('store', function () {
        it('creates an activity', function () {
            $this->actingAs(admin($this))
                ->post(route('calendar.admin.activities.store'), [
                    'name' => 'Nouvelle activité',
                    'activity_type' => 'fake',
                ])
                ->assertRedirect(route('calendar.admin.activities.index'));

            $this->assertDatabaseHas('calendar_activities', ['name' => 'Nouvelle activité']);
        });

        it('validates required fields', function () {
            $this->actingAs(admin($this))
                ->post(route('calendar.admin.activities.store'), [])
                ->assertSessionHasErrors(['name', 'activity_type']);
        });

        it('accepts optional fields', function () {
            $this->actingAs(admin($this))
                ->post(route('calendar.admin.activities.store'), [
                    'name' => 'Activité complète',
                    'activity_type' => 'fake',
                    'requires_subscription' => '1',
                    'max_participants' => '50',
                ])
                ->assertRedirect(route('calendar.admin.activities.index'));

            $this->assertDatabaseHas('calendar_activities', [
                'name' => 'Activité complète',
                'requires_subscription' => true,
                'max_participants' => 50,
            ]);
        });
    });

    describe('edit', function () {
        it('displays the edit form', function () {
            $admin = admin($this);
            $this->actingAs($admin);
            $activityId = createActivity($this, ['name' => 'Existante'], $admin->id);
            $activity = Activity::findOrFail($activityId);

            $this->get(route('calendar.admin.activities.edit', $activity))
                ->assertOk()
                ->assertSee('Existante');
        });

        it('denies access to non-admins', function () {
            $activityId = createActivity($this);
            $activity = Activity::findOrFail($activityId);

            $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
                ->get(route('calendar.admin.activities.edit', $activity))
                ->assertRedirect();
        });
    });

    describe('update', function () {
        it('updates an activity', function () {
            $activityId = createActivity($this, ['name' => 'Ancien nom']);
            $activity = Activity::findOrFail($activityId);

            $this->actingAs(admin($this))
                ->put(route('calendar.admin.activities.update', $activity), [
                    'name' => 'Nouveau nom',
                ])
                ->assertRedirect(route('calendar.admin.activities.index'));

            $this->assertDatabaseHas('calendar_activities', [
                'id' => $activity->id,
                'name' => 'Nouveau nom',
            ]);
        });

        it('validates name is required on update', function () {
            $activityId = createActivity($this);
            $activity = Activity::findOrFail($activityId);

            $this->actingAs(admin($this))
                ->put(route('calendar.admin.activities.update', $activity), ['name' => ''])
                ->assertSessionHasErrors(['name']);
        });
    });

    describe('destroy', function () {
        it('deletes an activity', function () {
            $activityId = createActivity($this);
            $activity = Activity::findOrFail($activityId);

            $this->actingAs(admin($this))
                ->delete(route('calendar.admin.activities.destroy', $activity))
                ->assertRedirect(route('calendar.admin.activities.index'));

            $this->assertDatabaseMissing('calendar_activities', ['id' => $activity->id]);
        });

        it('denies deletion for non-admins', function () {
            $activityId = createActivity($this);
            $activity = Activity::findOrFail($activityId);

            $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
                ->delete(route('calendar.admin.activities.destroy', $activity))
                ->assertRedirect();

            $this->assertDatabaseHas('calendar_activities', ['id' => $activity->id]);
        });
    });
});
