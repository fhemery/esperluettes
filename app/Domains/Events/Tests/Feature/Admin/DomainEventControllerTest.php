<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Events\Private\Models\StoredDomainEvent;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $mock = Mockery::mock(ProfilePublicApi::class)->shouldIgnoreMissing();
    $mock->shouldReceive('getPublicProfiles')->andReturn([]);
    $mock->shouldReceive('getPublicProfile')->andReturn(null);
    app()->instance(ProfilePublicApi::class, $mock);
});

describe('DomainEvent Admin Controller', function () {

    describe('index', function () {
        it('displays the list for admins', function () {
            StoredDomainEvent::create([
                'name'       => 'Test.SomethingHappened',
                'payload'    => [],
                'occurred_at' => now(),
            ]);

            $this->actingAs(admin($this))
                ->get(route('events.admin.domain-events.index'))
                ->assertOk()
                ->assertSee('SomethingHappened');
        });

        it('denies access to non-admins', function () {
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->get(route('events.admin.domain-events.index'))
                ->assertRedirect(route('dashboard'));
        });

        it('redirects unauthenticated users to login', function () {
            $this->get(route('events.admin.domain-events.index'))
                ->assertRedirect(route('login'));
        });

        it('allows access for moderators', function () {
            $user = alice($this, [], true, [Roles::MODERATOR]);

            $this->actingAs($user)
                ->get(route('events.admin.domain-events.index'))
                ->assertOk();
        });

        it('filters by event name', function () {
            StoredDomainEvent::create(['name' => 'Test.Visible', 'payload' => [], 'occurred_at' => now()]);
            StoredDomainEvent::create(['name' => 'Test.Hidden', 'payload' => [], 'occurred_at' => now()]);

            $this->actingAs(admin($this))
                ->get(route('events.admin.domain-events.index', ['name_filter' => 'Visible']))
                ->assertOk()
                ->assertSee('Visible')
                ->assertDontSee('Hidden');
        });

        it('filters by user_id', function () {
            StoredDomainEvent::create(['name' => 'Test.A', 'payload' => [], 'occurred_at' => now(), 'triggered_by_user_id' => 42]);
            StoredDomainEvent::create(['name' => 'Test.B', 'payload' => [], 'occurred_at' => now(), 'triggered_by_user_id' => 99]);

            $this->actingAs(admin($this))
                ->get(route('events.admin.domain-events.index', ['user_id' => 42]))
                ->assertOk()
                ->assertSee('Test.A')
                ->assertDontSee('Test.B');
        });
    });

    describe('show', function () {
        it('displays the event detail for admins', function () {
            $event = StoredDomainEvent::create([
                'name'        => 'Test.DetailEvent',
                'payload'     => ['key' => 'value'],
                'occurred_at' => now(),
            ]);

            $this->actingAs(admin($this))
                ->get(route('events.admin.domain-events.show', $event))
                ->assertOk()
                ->assertSee('Test.DetailEvent');
        });

        it('denies access to non-admins', function () {
            $event = StoredDomainEvent::create([
                'name'        => 'Test.Event',
                'payload'     => [],
                'occurred_at' => now(),
            ]);

            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->get(route('events.admin.domain-events.show', $event))
                ->assertRedirect(route('dashboard'));
        });
    });

    describe('bulkDestroy', function () {
        it('deletes selected events', function () {
            $event1 = StoredDomainEvent::create(['name' => 'Test.A', 'payload' => [], 'occurred_at' => now()]);
            $event2 = StoredDomainEvent::create(['name' => 'Test.B', 'payload' => [], 'occurred_at' => now()]);

            $this->actingAs(admin($this))
                ->post(route('events.admin.domain-events.bulk-destroy'), ['ids' => [$event1->id, $event2->id]])
                ->assertRedirect(route('events.admin.domain-events.index'));

            $this->assertDatabaseMissing('events_domain', ['id' => $event1->id]);
            $this->assertDatabaseMissing('events_domain', ['id' => $event2->id]);
        });

        it('validates that ids array is required', function () {
            $this->actingAs(admin($this))
                ->post(route('events.admin.domain-events.bulk-destroy'), [])
                ->assertSessionHasErrors(['ids']);
        });

        it('denies access to non-admins', function () {
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->post(route('events.admin.domain-events.bulk-destroy'), ['ids' => [1]])
                ->assertRedirect(route('dashboard'));
        });
    });

    describe('destroy', function () {
        it('deletes a single event', function () {
            $event = StoredDomainEvent::create([
                'name'        => 'Test.ToDelete',
                'payload'     => [],
                'occurred_at' => now(),
            ]);

            $this->actingAs(admin($this))
                ->delete(route('events.admin.domain-events.destroy', $event))
                ->assertRedirect(route('events.admin.domain-events.index'));

            $this->assertDatabaseMissing('events_domain', ['id' => $event->id]);
        });

        it('denies access to non-admins', function () {
            $event = StoredDomainEvent::create([
                'name'        => 'Test.Event',
                'payload'     => [],
                'occurred_at' => now(),
            ]);

            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->delete(route('events.admin.domain-events.destroy', $event))
                ->assertRedirect(route('dashboard'));
        });
    });
});
