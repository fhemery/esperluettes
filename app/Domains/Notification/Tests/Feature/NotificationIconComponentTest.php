<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
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
        expect($rendered)->not->toContain('unread-badge');
    });

    it('shows the correct unread count badge for logged-in users', function () {
        $user = alice($this);
        $this->actingAs($user);

        /** @var NotificationPublicApi $api */
        $api = app(NotificationPublicApi::class);
        // Create one unread notification
        $api->createNotification([$user->id], 'news::notification.posted', [
            'title' => 'Hello',
            'slug' => 'hello',
        ], $user->id);

        $rendered = Blade::render('<x-notification::notification-icon-component />');

        expect($rendered)->toContain(route('notifications.index'));
        expect($rendered)->toContain('unread-badge');
        expect($rendered)->toMatch('/bg-accent[^>]*>\s*1\s*</');
    });
});
