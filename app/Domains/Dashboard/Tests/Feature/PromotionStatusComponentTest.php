<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('PromotionStatusComponent', function () {

    it('shows error when user is not authenticated', function () {
        $html = Blade::render('<x-dashboard::promotion-status-component />');

        expect($html)->toContain(__('dashboard::promotion.errors.not_authenticated'));
    });

    it('shows eligibility info for non-confirmed user', function () {
        setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 10);
        setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 604800); // 7 days

        $user = registerUserThroughForm($this, [
            'email' => 'probation@example.com',
        ], true, [Roles::USER]);

        $this->actingAs($user);

        $html = Blade::render('<x-dashboard::promotion-status-component />');

        expect($html)
            ->toContain(__('dashboard::promotion.title'))
            ->toContain(__('dashboard::promotion.current_status'))
            ->toContain(__('dashboard::promotion.requirements_intro'))
            ->toContain(__('dashboard::promotion.button_request'));
    });

    it('shows progress bars with correct values', function () {
        setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 10);
        setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 604800); // 7 days

        $user = registerUserThroughForm($this, [
            'email' => 'progress@example.com',
        ], true, [Roles::USER]);

        $this->actingAs($user);

        $html = Blade::render('<x-dashboard::promotion-status-component />');

        // Should show days and comments labels
        expect($html)
            ->toContain(__('dashboard::promotion.days_label'))
            ->toContain(__('dashboard::promotion.comments_label'));
    });

    it('shows pending state when user has pending request', function () {
        setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
        setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

        $user = registerUserThroughForm($this, [
            'email' => 'pending@example.com',
        ], true, [Roles::USER]);

        // Create a pending promotion request
        createPromotionRequest($user, commentCount: 5);

        $this->actingAs($user);

        $html = Blade::render('<x-dashboard::promotion-status-component />');

        expect($html)
            ->toContain(__('dashboard::promotion.button_pending'))
            ->toContain(__('dashboard::promotion.pending_message'));
    });

    it('shows rejection info when user was rejected', function () {
        setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
        setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);

        $user = registerUserThroughForm($this, [
            'email' => 'rejected@example.com',
        ], true, [Roles::USER]);
        $admin = admin($this);

        // Create and reject a promotion request
        $request = createPromotionRequest($user, commentCount: 5);
        rejectPromotionRequest($request, $admin, 'Comments need more substance');

        $this->actingAs($user);

        $html = Blade::render('<x-dashboard::promotion-status-component />');

        expect($html)
            ->toContain(__('dashboard::promotion.rejection_title'))
            ->toContain('Comments need more substance')
            ->toContain(__('dashboard::promotion.button_request'));
    });
});
