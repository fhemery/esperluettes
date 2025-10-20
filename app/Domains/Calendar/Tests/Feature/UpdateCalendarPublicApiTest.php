<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Public\Contracts\ActivityToUpdateDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CalendarPublicApi - update', function () {
    it('rejects update if caller is not ADMIN or TECH_ADMIN', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = $api->create(makeActivityCreateDto(), $adminUser->id);

        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $update = new ActivityToUpdateDto(
            name: 'Updated Name',
            activity_type: 'fake',
            description: null,
        );

        expect(function () use ($api, $id, $update, $user) {
            $api->update($id, $update, $user->id);
        })->toThrow(UnauthorizedException::class);
    });

    it('rejects update if activity_type changes (immutable)', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });
        $registry->register('other', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = $api->create(makeActivityCreateDto(), $adminUser->id);

        $update = new ActivityToUpdateDto(
            name: 'Updated Name',
            activity_type: 'other',
            description: null,
        );

        expect(function () use ($api, $id, $update, $adminUser) {
            $api->update($id, $update, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('allows full replace update for admins and reflects in getOne', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = $api->create(makeActivityCreateDto(), $adminUser->id);

        $update = new ActivityToUpdateDto(
            name: 'Updated Name',
            activity_type: 'fake',
            description: 'New description',
            image_path: 'img.png',
            role_restrictions: [Roles::USER],
            requires_subscription: true,
            max_participants: 50,
            preview_starts_at: now(),
            active_starts_at: now()->addDay(),
            active_ends_at: now()->addDays(2),
            archived_at: null,
        );

        $api->update($id, $update, $adminUser->id);

        $dto = $api->getOne($id, $adminUser->id);
        expect($dto->name)->toBe('Updated Name');
        expect($dto->description)->toContain('New description');
        expect($dto->image_path)->toBe('img.png');
        expect($dto->role_restrictions)->toEqual([Roles::USER]);
        expect($dto->requires_subscription)->toBeTrue();
        expect($dto->max_participants)->toBe(50);
    });

    it('rejects update when required fields are only whitespace (trimmed) or type unknown', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = $api->create(makeActivityCreateDto(), $adminUser->id);

        // Name blank
        $invalid = new ActivityToUpdateDto(
            name: '   ',
            activity_type: 'fake',
        );
        expect(function () use ($api, $id, $invalid, $adminUser) {
            $api->update($id, $invalid, $adminUser->id);
        })->toThrow(ValidationException::class);

        // Unknown type (still immutable check, but keep type same path)
        $registry->register('other', new class { });
        $invalid2 = new ActivityToUpdateDto(
            name: 'Ok',
            activity_type: 'unknown',
        );
        expect(function () use ($api, $id, $invalid2, $adminUser) {
            $api->update($id, $invalid2, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('rejects update when active_starts_at is before preview_starts_at', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = $api->create(makeActivityCreateDto(), $adminUser->id);

        $now = now();
        $bad = new ActivityToUpdateDto(
            name: 'Name', activity_type: 'fake',
            preview_starts_at: $now->copy()->addDay(),
            active_starts_at: $now,
        );
        expect(function () use ($api, $id, $bad, $adminUser) {
            $api->update($id, $bad, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('rejects update when active_ends_at is before active_starts_at', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = $api->create(makeActivityCreateDto(), $adminUser->id);

        $now = now();
        $bad = new ActivityToUpdateDto(
            name: 'Name', activity_type: 'fake',
            preview_starts_at: $now,
            active_starts_at: $now->copy()->addDays(2),
            active_ends_at: $now->copy()->addDay(),
        );
        expect(function () use ($api, $id, $bad, $adminUser) {
            $api->update($id, $bad, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('rejects update when archived_at is before active_ends_at', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = $api->create(makeActivityCreateDto(), $adminUser->id);

        $now = now();
        $bad = new ActivityToUpdateDto(
            name: 'Name', activity_type: 'fake',
            preview_starts_at: $now,
            active_starts_at: $now->copy()->addDay(),
            active_ends_at: $now->copy()->addDays(2),
            archived_at: $now->copy()->addDay(),
        );
        expect(function () use ($api, $id, $bad, $adminUser) {
            $api->update($id, $bad, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('allows update with equal boundaries across preview/active start/active end/archive', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = $api->create(makeActivityCreateDto(), $adminUser->id);

        $now = now();
        $ok = new ActivityToUpdateDto(
            name: 'Name', activity_type: 'fake',
            preview_starts_at: $now,
            active_starts_at: $now,
            active_ends_at: $now,
            archived_at: $now,
        );
        $api->update($id, $ok, $adminUser->id);
    });
});
