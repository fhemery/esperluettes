<?php

use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Message\Private\Support\FeatureToggles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('MessageIconComponent', function () {
    it('should not display when feature toggle is disabled', function () {
        createFeatureToggle($this, new FeatureToggle(FeatureToggles::ActiveFeatureName, FeatureToggles::DomainName, access: FeatureToggleAccess::OFF));

        $rendered = Blade::render('<x-message::message-icon-component />');

        expect($rendered)->toBe('');
    });

    describe('when feature toggle is enabled', function () {

        beforeEach(function () {
            createFeatureToggle($this, new FeatureToggle(FeatureToggles::ActiveFeatureName, FeatureToggles::DomainName, access: FeatureToggleAccess::ON));
        });

        it('displays the icon for admins even with zero messages', function () {
            $admin = admin($this);

            $this->actingAs($admin);

            $rendered = Blade::render('<x-message::message-icon-component />');

            expect($rendered)->toContain('href="' . route('messages.index') . '"');
            expect($rendered)->not->toContain('unread-badge'); // No unread badge
        });

        it('displays the icon for tech-admins even with zero messages', function () {
            $user = techAdmin($this);

            $this->actingAs($user);

            $rendered = Blade::render('<x-message::message-icon-component />');

            expect($rendered)->toContain('href="' . route('messages.index') . '"');
            expect($rendered)->not->toContain('unread-badge');
        });

        it('displays the icon for moderators even with zero messages', function () {
            $user = moderator($this);

            $this->actingAs($user);

            $rendered = Blade::render('<x-message::message-icon-component />');

            expect($rendered)->toContain('href="' . route('messages.index') . '"');
            expect($rendered)->not->toContain('unread-badge');
        });

        it('displays the icon for regular users who have messages', function () {
            $sender = admin($this);
            $recipient = alice($this);

            sendMessageToUsers($this, $sender, 'Test Message', '<p>Hello!</p>', [$recipient->id]);

            $this->actingAs($recipient);

            $rendered = Blade::render('<x-message::message-icon-component />');

            expect($rendered)->toContain('href="' . route('messages.index') . '"');
            expect($rendered)->toContain('unread-badge'); // Unread badge present
            expect($rendered)->toMatch('/bg-accent[^>]*>\s*1\s*</'); // Unread count
        });

        it('does not display the icon for regular users without messages', function () {
            $user = alice($this);

            $this->actingAs($user);

            $rendered = Blade::render('<x-message::message-icon-component />');

            expect($rendered)->not->toContain('href="' . route('messages.index') . '"');
            expect($rendered)->toBe(''); // Component renders nothing
        });

        it('shows the correct unread count badge', function () {
            $sender = admin($this);
            $recipient = alice($this);

            // Send 3 messages
            sendMessageToUsers($this, $sender, 'Message 1', '<p>Content 1</p>', [$recipient->id]);
            sendMessageToUsers($this, $sender, 'Message 2', '<p>Content 2</p>', [$recipient->id]);
            sendMessageToUsers($this, $sender, 'Message 3', '<p>Content 3</p>', [$recipient->id]);

            $this->actingAs($recipient);

            $rendered = Blade::render('<x-message::message-icon-component />');

            expect($rendered)->toContain('href="' . route('messages.index') . '"');
            expect($rendered)->toContain('unread-badge');
            expect($rendered)->toMatch('/bg-accent[^>]*>\s*3\s*</'); // Unread count of 3
        });

        it('does not show unread badge when count is zero', function () {
            $sender = admin($this);
            $recipient = alice($this);

            $message = sendMessageToUsers($this, $sender, 'Test Message', '<p>Hello!</p>', [$recipient->id]);

            // Mark as read
            $delivery = getDeliveryForUser($message->id, $recipient->id);
            $delivery->markAsRead();

            $this->actingAs($recipient);

            $rendered = Blade::render('<x-message::message-icon-component />');

            // Icon should still be visible (user has messages)
            expect($rendered)->toContain('href="' . route('messages.index') . '"');
            // But no unread badge
            expect($rendered)->not->toContain('unread-badge');
        });

        it('does not display anything for guests', function () {
            // No actingAs - testing as guest
            Auth::logout();

            $rendered = Blade::render('<x-message::message-icon-component />');

            expect($rendered)->toBe(''); // Component renders nothing for guests
        });

        it('updates visibility when admin receives messages', function () {
            $admin = admin($this);
            $admin2 = admin($this, ['name' => 'Admin2', 'email' => 'admin2@example.com']);

            // Admin has icon visible even without messages
            $this->actingAs($admin);

            $rendered = Blade::render('<x-message::message-icon-component />');
            expect($rendered)->toContain('href="' . route('messages.index') . '"');
            expect($rendered)->not->toContain('unread-badge'); // No unread badge

            // Another admin sends message to this admin
            sendMessageToUsers($this, $admin2, 'Message to Admin', '<p>Hello Admin!</p>', [$admin->id]);

            // Icon still visible with unread count
            $this->actingAs($admin);
            $rendered = Blade::render('<x-message::message-icon-component />');
            expect($rendered)->toContain('href="' . route('messages.index') . '"');
            expect($rendered)->toContain('unread-badge');
            expect($rendered)->toMatch('/bg-accent[^>]*>\s*1\s*</'); // Unread count of 1
        });
    });
});
