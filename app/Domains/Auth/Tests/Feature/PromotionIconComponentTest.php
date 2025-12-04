<?php

use App\Domains\Auth\Private\Models\PromotionRequest;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('PromotionIconComponent', function () {
    
    it('does not display anything for guests', function () {
        Auth::logout();

        $rendered = Blade::render('<x-auth::promotion-icon-component />');

        expect($rendered)->toBe('');
    });

    it('does not display the icon for admins when there are no pending requests', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $rendered = Blade::render('<x-auth::promotion-icon-component />');

        expect($rendered)->toBe('');
    });

    it('does not display the icon for tech-admins when there are no pending requests', function () {
        $user = techAdmin($this);
        $this->actingAs($user);

        $rendered = Blade::render('<x-auth::promotion-icon-component />');

        expect($rendered)->toBe('');
    });

    it('does not display the icon for moderators when there are no pending requests', function () {
        $user = moderator($this);
        $this->actingAs($user);

        $rendered = Blade::render('<x-auth::promotion-icon-component />');

        expect($rendered)->toBe('');
    });

    it('does not display the icon for regular users even if there are pending requests', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create a pending promotion request for another user
        $otherUser = registerUserThroughForm($this, [
            'name' => 'OtherUser',
            'email' => 'other@example.com',
        ], true, [Roles::USER]);
        createPromotionRequest($otherUser, commentCount: 5);

        $rendered = Blade::render('<x-auth::promotion-icon-component />');

        expect($rendered)->toBe('');
    });

    it('displays the icon for admins when there are pending requests', function () {
        $admin = admin($this);
        
        // Create a pending promotion request
        $user = registerUserThroughForm($this, [
            'name' => 'PromoUser',
            'email' => 'promo@example.com',
        ], true, [Roles::USER]);
        createPromotionRequest($user, commentCount: 5);

        $this->actingAs($admin);

        $rendered = Blade::render('<x-auth::promotion-icon-component />');

        expect($rendered)->toContain('href="' . route('auth.admin.promotion-requests.index') . '"');
        expect($rendered)->toContain('psychiatry');
        expect($rendered)->toContain('pending-badge');
    });

    it('displays the icon for tech-admins when there are pending requests', function () {
        $techAdmin = techAdmin($this);
        
        // Create a pending promotion request
        $user = registerUserThroughForm($this, [
            'name' => 'PromoUser2',
            'email' => 'promo2@example.com',
        ], true, [Roles::USER]);
        createPromotionRequest($user, commentCount: 5);

        $this->actingAs($techAdmin);

        $rendered = Blade::render('<x-auth::promotion-icon-component />');

        expect($rendered)->toContain('href="' . route('auth.admin.promotion-requests.index') . '"');
        expect($rendered)->toContain('psychiatry');
    });

    it('displays the icon for moderators when there are pending requests', function () {
        $moderator = moderator($this);
        
        // Create a pending promotion request
        $user = registerUserThroughForm($this, [
            'name' => 'PromoUser3',
            'email' => 'promo3@example.com',
        ], true, [Roles::USER]);
        createPromotionRequest($user, commentCount: 5);

        $this->actingAs($moderator);

        $rendered = Blade::render('<x-auth::promotion-icon-component />');

        expect($rendered)->toContain('href="' . route('auth.admin.promotion-requests.index') . '"');
        expect($rendered)->toContain('psychiatry');
    });

    it('shows the correct pending count badge', function () {
        $admin = admin($this);
        
        // Create 3 pending promotion requests
        foreach ([1, 2, 3] as $i) {
            $user = registerUserThroughForm($this, [
                'name' => "PromoUser{$i}",
                'email' => "promo{$i}@example.com",
            ], true, [Roles::USER]);
            createPromotionRequest($user, commentCount: 5);
        }

        // Create 1 accepted request (should not count)
        $acceptedUser = registerUserThroughForm($this, [
            'name' => 'AcceptedUser',
            'email' => 'accepted@example.com',
        ], true, [Roles::USER]);
        $acceptedRequest = createPromotionRequest($acceptedUser, commentCount: 5);
        $acceptedRequest->accept($admin->id);

        $this->actingAs($admin);

        $rendered = Blade::render('<x-auth::promotion-icon-component />');

        expect($rendered)->toContain('pending-badge');
        expect($rendered)->toMatch('/bg-accent[^>]*>\s*3\s*</');
    });

});
