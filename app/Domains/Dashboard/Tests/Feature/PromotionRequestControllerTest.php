<?php

use App\Domains\Auth\Private\Models\PromotionRequest;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Dashboard Promotion Request Controller', function () {

    describe('POST /dashboard/promotion/request', function () {

        it('requires authentication', function () {
            $response = $this->post(route('dashboard.promotion.request'));

            $response->assertRedirect(route('login'));
        });

        it('denies access to USER_CONFIRMED users', function () {
            $user = registerUserThroughForm($this, [
                'email' => 'confirmed@example.com',
            ], true, [Roles::USER_CONFIRMED]);

            $response = $this->actingAs($user)
                ->post(route('dashboard.promotion.request'));

            // Role middleware redirects unauthorized users
            $response->assertStatus(302);
        });

        it('creates promotion request when eligible', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = registerUserThroughForm($this, [
                'email' => 'eligible@example.com',
            ], true, [Roles::USER]);

            $response = $this->actingAs($user)
                ->post(route('dashboard.promotion.request'));

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('success', __('dashboard::promotion.success_message'));

            // Verify request was created
            $this->assertDatabaseHas('user_promotion_request', [
                'user_id' => $user->id,
                'status' => PromotionRequest::STATUS_PENDING,
            ]);
        });

        it('redirects with error when criteria not met', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 100);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = registerUserThroughForm($this, [
                'email' => 'noteligible@example.com',
            ], true, [Roles::USER]);

            $response = $this->actingAs($user)
                ->post(route('dashboard.promotion.request'));

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHasErrors('promotion');

            // Verify no request was created
            $this->assertDatabaseMissing('user_promotion_request', [
                'user_id' => $user->id,
            ]);
        });

        it('redirects with error when already has pending request', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = registerUserThroughForm($this, [
                'email' => 'alreadypending@example.com',
            ], true, [Roles::USER]);

            // Create existing pending request
            createPromotionRequest($user, commentCount: 5);

            $response = $this->actingAs($user)
                ->post(route('dashboard.promotion.request'));

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHasErrors('promotion');

            // Should still have only one request
            $count = PromotionRequest::where('user_id', $user->id)->count();
            expect($count)->toBe(1);
        });

        it('allows new request after previous rejection when eligible', function () {
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
            setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

            $user = registerUserThroughForm($this, [
                'email' => 'afterrejection@example.com',
            ], true, [Roles::USER]);
            $admin = admin($this);

            // Create and reject a request
            $request = createPromotionRequest($user, commentCount: 5);
            rejectPromotionRequest($request, $admin, 'Try again');

            $response = $this->actingAs($user)
                ->post(route('dashboard.promotion.request'));

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('success', __('dashboard::promotion.success_message'));

            // Should now have 2 requests
            $count = PromotionRequest::where('user_id', $user->id)->count();
            expect($count)->toBe(2);
        });

    });

    describe('Dashboard index isConfirmed flag', function () {

        it('shows PromotionStatusComponent for non-confirmed users', function () {
            $user = registerUserThroughForm($this, [
                'email' => 'nonconfirmed@example.com',
            ], true, [Roles::USER]);

            $response = $this->actingAs($user)
                ->get(route('dashboard'));

            $response->assertOk();
            $response->assertSee(__('dashboard::promotion.title'));
        });

        it('shows KeepWritingComponent for confirmed users', function () {
            $user = registerUserThroughForm($this, [
                'email' => 'confirmed2@example.com',
            ], true, [Roles::USER_CONFIRMED]);

            $response = $this->actingAs($user)
                ->get(route('dashboard'));

            $response->assertOk();
            $response->assertSee(__('story::keep-writing.title'));
        });

    });

});
