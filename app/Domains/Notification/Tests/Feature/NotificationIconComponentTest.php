<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('NotificationIconComponent', function () {
    it('does not display anything for guests', function () {
        Auth::logout();

        $rendered = Blade::render('<x-notification::notification-icon-component />');

        expect($rendered)->toBe('');
    });

    it('displays the bell for logged-in users even with zero unread', function () {
        $user = alice($this);
        $this->actingAs($user);

        $rendered = Blade::render('<x-notification::notification-icon-component />');

        expect($rendered)->toContain(route('notifications.index'));
        expect($rendered)->toContain('initialCount: 0');
    });

    it('shows the correct unread count badge for logged-in users', function () {
        $user = alice($this);
        $this->actingAs($user);

        /** @var NotificationPublicApi $api */
        $api = app(NotificationPublicApi::class);
        
        // Register test notification type
        $factory = app(\App\Domains\Notification\Public\Services\NotificationFactory::class);
        $factory->register(TestNotificationContent::type(), TestNotificationContent::class);
        
        // Create one unread notification
        $api->createNotification([$user->id], new TestNotificationContent(), $user->id);

        $rendered = Blade::render('<x-notification::notification-icon-component />');

        expect($rendered)->toContain(route('notifications.index'));
        expect($rendered)->toContain('unread-badge');
        expect($rendered)->toContain('initialCount: 1');
    });
});
