<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CalendarPublicApi - delete', function () {
    it('rejects delete if caller is not ADMIN or TECH_ADMIN', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        registerFakeActivityType($registry);

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = createActivity($this);

        $user = alice($this, roles: [Roles::USER]);
        $this->actingAs($user);

        expect(function () use ($api, $id, $user) {
            $api->delete($id, $user->id);
        })->toThrow(UnauthorizedException::class);
    });

    it('allows hard delete for admins and getOne returns 404 afterwards', function () {
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        registerFakeActivityType($registry);

        /** @var CalendarPublicApi $api */
        $api = app(CalendarPublicApi::class);
        $adminUser = admin($this);
        $this->actingAs($adminUser);
        $id = createActivity($this);

        $api->delete($id, $adminUser->id);

        expect(function () use ($api, $id, $adminUser) {
            $api->getOne($id, $adminUser->id);
        })->toThrow(NotFoundHttpException::class);
    });
});
