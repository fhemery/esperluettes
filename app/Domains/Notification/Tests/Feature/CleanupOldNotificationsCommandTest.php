<?php

use App\Domains\Notification\Public\Events\NotificationsCleanedUp;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CleanupOldNotifications Command', function () {
    beforeEach(function () {
        // Register test notification type
        $factory = app(\App\Domains\Notification\Public\Services\NotificationFactory::class);
        $factory->register(TestNotificationContent::type(), TestNotificationContent::class);
    });

    it('deletes notifications older than 30 days', function () {
        $user = alice($this);

        // Create a notification from 31 days ago
        $oldNotificationId = DB::table('notifications')->insertGetId([
            'source_user_id' => $user->id,
            'content_key' => TestNotificationContent::type(),
            'content_data' => json_encode(['message' => 'Old notification']),
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ]);

        DB::table('notification_reads')->insert([
            'notification_id' => $oldNotificationId,
            'user_id' => $user->id,
            'read_at' => null,
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ]);

        // Create a recent notification (29 days ago)
        $recentNotificationId = DB::table('notifications')->insertGetId([
            'source_user_id' => $user->id,
            'content_key' => TestNotificationContent::type(),
            'content_data' => json_encode(['message' => 'Recent notification']),
            'created_at' => now()->subDays(29),
            'updated_at' => now()->subDays(29),
        ]);

        DB::table('notification_reads')->insert([
            'notification_id' => $recentNotificationId,
            'user_id' => $user->id,
            'read_at' => null,
            'created_at' => now()->subDays(29),
            'updated_at' => now()->subDays(29),
        ]);

        // Run the command
        $this->artisan('notifications:cleanup')
            ->assertExitCode(0);

        // Old notification should be deleted
        expect(DB::table('notifications')->where('id', $oldNotificationId)->exists())->toBeFalse();
        expect(DB::table('notification_reads')->where('notification_id', $oldNotificationId)->exists())->toBeFalse();

        // Recent notification should still exist
        expect(DB::table('notifications')->where('id', $recentNotificationId)->exists())->toBeTrue();
        expect(DB::table('notification_reads')->where('notification_id', $recentNotificationId)->exists())->toBeTrue();
    });

    it('deletes notifications with unknown types regardless of age', function () {
        $user = alice($this);

        // Create a notification with unknown type from today
        $unknownNotificationId = DB::table('notifications')->insertGetId([
            'source_user_id' => $user->id,
            'content_key' => 'unknown.notification.type',
            'content_data' => json_encode(['some' => 'data']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notification_reads')->insert([
            'notification_id' => $unknownNotificationId,
            'user_id' => $user->id,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a valid notification
        $validNotificationId = DB::table('notifications')->insertGetId([
            'source_user_id' => $user->id,
            'content_key' => TestNotificationContent::type(),
            'content_data' => json_encode(['message' => 'Valid notification']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notification_reads')->insert([
            'notification_id' => $validNotificationId,
            'user_id' => $user->id,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run the command
        $this->artisan('notifications:cleanup')
            ->assertExitCode(0);

        // Unknown type should be deleted (even though it's recent)
        expect(DB::table('notifications')->where('id', $unknownNotificationId)->exists())->toBeFalse();
        expect(DB::table('notification_reads')->where('notification_id', $unknownNotificationId)->exists())->toBeFalse();

        // Valid notification should still exist
        expect(DB::table('notifications')->where('id', $validNotificationId)->exists())->toBeTrue();
        expect(DB::table('notification_reads')->where('notification_id', $validNotificationId)->exists())->toBeTrue();
    });

    it('reports the number of deleted notifications', function () {
        $user = alice($this);

        // Create 2 old notifications
        for ($i = 0; $i < 2; $i++) {
            $id = DB::table('notifications')->insertGetId([
                'source_user_id' => $user->id,
                'content_key' => TestNotificationContent::type(),
                'content_data' => json_encode(['message' => "Old $i"]),
                'created_at' => now()->subDays(31),
                'updated_at' => now()->subDays(31),
            ]);

            DB::table('notification_reads')->insert([
                'notification_id' => $id,
                'user_id' => $user->id,
                'read_at' => null,
                'created_at' => now()->subDays(31),
                'updated_at' => now()->subDays(31),
            ]);
        }

        // Create 1 unknown type notification
        $unknownId = DB::table('notifications')->insertGetId([
            'source_user_id' => $user->id,
            'content_key' => 'unknown.type',
            'content_data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notification_reads')->insert([
            'notification_id' => $unknownId,
            'user_id' => $user->id,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run the command and check output
        $this->artisan('notifications:cleanup')
            ->expectsOutput('Deleted 2 old notifications (>30 days)')
            ->expectsOutput('Deleted 1 notifications with unknown types')
            ->expectsOutput('Total: 3 notifications cleaned up')
            ->assertExitCode(0);
    });

    it('handles the case when there are no notifications to clean', function () {
        $user = alice($this);

        // Create only a recent valid notification
        makeNotification([$user->id]);

        // Run the command
        $this->artisan('notifications:cleanup')
            ->expectsOutput('Deleted 0 old notifications (>30 days)')
            ->expectsOutput('Deleted 0 notifications with unknown types')
            ->expectsOutput('Total: 0 notifications cleaned up')
            ->assertExitCode(0);
    });

    it('dispatches NotificationsCleanedUp event with cleanup counts', function () {
        Event::fake();
        $user = alice($this);

        // Create 1 old notification
        $oldId = DB::table('notifications')->insertGetId([
            'source_user_id' => $user->id,
            'content_key' => TestNotificationContent::type(),
            'content_data' => json_encode(['message' => 'Old']),
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ]);

        DB::table('notification_reads')->insert([
            'notification_id' => $oldId,
            'user_id' => $user->id,
            'read_at' => null,
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ]);

        // Create 1 unknown type notification
        $unknownId = DB::table('notifications')->insertGetId([
            'source_user_id' => $user->id,
            'content_key' => 'unknown.type',
            'content_data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notification_reads')->insert([
            'notification_id' => $unknownId,
            'user_id' => $user->id,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run the command
        $this->artisan('notifications:cleanup')
            ->assertExitCode(0);

        // Assert event was dispatched with correct counts
        Event::assertDispatched(NotificationsCleanedUp::class, function ($event) {
            return $event->oldNotificationsDeleted === 1
                && $event->unknownTypesDeleted === 1
                && $event->total() === 2;
        });
    });
});
