<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Public\Contracts\ActivityDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CalendarPublicApi - getOne', function () {
    it('returns 404 for draft to non-admin/tech-admin users', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        registerFakeActivityType($registry);

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        // Create a draft (no preview date)
        $id = createActivity($this);

        // Regular user tries to fetch
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        expect(function () use ($api, $id, $user) {
            $api->getOne($id, $user->id);
        })->toThrow(NotFoundHttpException::class);
    });

    it('allows getOne for draft to ADMIN and TECH_ADMIN', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        registerFakeActivityType($registry);

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        $id = createActivity($this);
        $fetched = $api->getOne($id, $adminUser->id);

        expect($fetched)->toBeInstanceOf(ActivityDto::class);
        expect($fetched->id)->toBe($id);

        $tech = techAdmin($this);
        $this->actingAs($tech);
        $fetched2 = $api->getOne($id, $tech->id);
        expect($fetched2->id)->toBe($id);
    });

    it('allows getOne for non-draft to USER and USER_CONFIRMED', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        registerFakeActivityType($registry);

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);

        // Make it non-draft by setting preview_starts_at to now
        $id = createActivity($this, [
            'preview_starts_at' => now(),
        ]);

        $user1 = alice($this, roles: [Roles::USER]);
        $this->actingAs($user1);
        $dto1 = $api->getOne($id, $user1->id);
        expect($dto1->id)->toBe($id);

        $user2 = bob($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user2);
        $dto2 = $api->getOne($id, $user2->id);
        expect($dto2->id)->toBe($id);
    });
});
