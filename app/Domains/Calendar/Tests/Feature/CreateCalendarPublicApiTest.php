<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Public\Contracts\ActivityDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CalendarPublicApi - create', function () {
    it('rejects creation if caller is not ADMIN or TECH_ADMIN', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $dto = makeActivityCreateDto();

        expect(function () use ($api, $dto, $user) {
            $api->create($dto, $user->id);
        })->toThrow(UnauthorizedException::class);
    });

    it('rejects creation when required fields are only whitespace (trimmed)', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        // Name blank
        $invalid = makeActivityCreateDto(['name' => '   ']);
        expect(function () use ($api, $invalid, $adminUser) {
            $api->create($invalid, $adminUser->id);
        })->toThrow(ValidationException::class);

        // Unknown type
        $invalid2 = makeActivityCreateDto(['activity_type' => 'unknown']);
        expect(function () use ($api, $invalid2, $adminUser) {
            $api->create($invalid2, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('rejects when active_starts_at is before preview_starts_at', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        $now = now();
        $dto = makeActivityCreateDto([
            'preview_starts_at' => $now->copy()->addDay(),
            'active_starts_at' => $now,
        ]);
        expect(function () use ($api, $dto, $adminUser) {
            $api->create($dto, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('rejects when active_ends_at is before active_starts_at', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        $now = now();
        $dto = makeActivityCreateDto([
            'preview_starts_at' => $now,
            'active_starts_at' => $now->copy()->addDays(2),
            'active_ends_at' => $now->copy()->addDay(),
        ]);
        expect(function () use ($api, $dto, $adminUser) {
            $api->create($dto, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('rejects when archived_at is before active_ends_at', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        $now = now();
        $dto = makeActivityCreateDto([
            'preview_starts_at' => $now,
            'active_starts_at' => $now->copy()->addDay(),
            'active_ends_at' => $now->copy()->addDays(2),
            'archived_at' => $now->copy()->addDay(),
        ]);
        expect(function () use ($api, $dto, $adminUser) {
            $api->create($dto, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('allows equal boundaries across preview/active start/active end/archive', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        $now = now();
        $dto = makeActivityCreateDto([
            'preview_starts_at' => $now,
            'active_starts_at' => $now,
            'active_ends_at' => $now,
            'archived_at' => $now,
        ]);
        $id = $api->create($dto, $adminUser->id);
        expect($id)->toBeInt();
    });

    it('sanitizes description using admin-content profile (allows links, strips scripts)', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        $dirty = '<script>alert(1)</script><p>Hello <a href="https://example.com" target="_blank" rel="noopener">link</a></p>';
        $id = $api->create(makeActivityCreateDto([
            'description' => $dirty,
        ]), $adminUser->id);

        $dto = $api->getOne($id, $adminUser->id);
        expect($dto->description)->toMatch('/<p>Hello/');
        expect($dto->description)->toMatch('/<a href="https:\/\/example\.com"/');
        expect($dto->description)->not()->toMatch('/<script>/');
    });
    it('rejects creation when mandatory fields are missing', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        // Missing name
        $invalid = makeActivityCreateDto(['name' => null]);
        expect(function () use ($api, $invalid, $adminUser) {
            $api->create($invalid, $adminUser->id);
        })->toThrow(ValidationException::class);

        // Missing activity_type
        $invalid2 = makeActivityCreateDto(['activity_type' => null]);
        expect(function () use ($api, $invalid2, $adminUser) {
            $api->create($invalid2, $adminUser->id);
        })->toThrow(ValidationException::class);
    });

    it('allows creation when caller has admin rights', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $registry->register('fake', new class { });

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        $dto = makeActivityCreateDto();
        $id = $api->create($dto, $adminUser->id);

        expect($id)->toBeInt();
        expect($id)->toBeGreaterThan(0);

        $fetched = $api->getOne($id, $adminUser->id);
        expect($fetched)->toBeInstanceOf(ActivityDto::class);
        expect($fetched->id)->toBe($id);
        expect($fetched->name)->toBe($dto->name);
        expect($fetched->activity_type)->toBe($dto->activity_type);
    });
});
