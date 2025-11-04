<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('NotificationPublicApi::deleteNotificationsByType()', function () {
    it('returns 0 when no notifications exist for the given type', function () {
        $api = app(NotificationPublicApi::class);
        $alice = alice($this);
        
        $count = $api->deleteNotificationsByType('non.existent.type');
        
        expect($count)->toBe(0);
    });

    it('deletes all notifications of the specified type and returns count', function () {
        $api = app(NotificationPublicApi::class);
        $alice = alice($this);
        $bob = bob($this);
        
        // Create multiple notifications of the target type
        $content1 = new TestNotificationContent('message 1');
        $content2 = new TestNotificationContent('message 2');
        $api->createNotification([$alice->id], $content1);
        $api->createNotification([$bob->id], $content2);
        
        // Verify they exist
        expect(DB::table('notifications')->where('content_key', 'test.notification')->count())->toBe(2);
        
        // Delete by type
        $count = $api->deleteNotificationsByType('test.notification');
        
        expect($count)->toBe(2);
        expect(DB::table('notifications')->where('content_key', 'test.notification')->count())->toBe(0);
    });

    it('cascades deletion to notification_reads', function () {
        $api = app(NotificationPublicApi::class);
        $alice = alice($this);
        $bob = bob($this);
        
        // Create a notification for multiple users
        $content = new TestNotificationContent('broadcast message');
        $api->createNotification([$alice->id, $bob->id], $content);
        
        // Verify notification_reads exist
        expect(DB::table('notification_reads')->where('user_id', $alice->id)->count())->toBe(1);
        expect(DB::table('notification_reads')->where('user_id', $bob->id)->count())->toBe(1);
        
        // Delete notifications
        $api->deleteNotificationsByType('test.notification');
        
        // Verify notification_reads were cascaded
        expect(DB::table('notification_reads')->where('user_id', $alice->id)->count())->toBe(0);
        expect(DB::table('notification_reads')->where('user_id', $bob->id)->count())->toBe(0);
    });

    it('only deletes notifications with exact content_key match', function () {
        $api = app(NotificationPublicApi::class);
        $alice = alice($this);
        
        // Create notifications with similar but different types
        $content1 = new TestNotificationContent('message 1');
        $content2 = new AnotherTestNotificationContent('message 2');
        
        $api->createNotification([$alice->id], $content1);
        $api->createNotification([$alice->id], $content2);
        
        // Verify both exist
        expect(DB::table('notifications')->where('content_key', 'test.notification')->count())->toBe(1);
        expect(DB::table('notifications')->where('content_key', 'another.test.notification')->count())->toBe(1);
        
        // Delete only one type
        $count = $api->deleteNotificationsByType('test.notification');
        
        expect($count)->toBe(1);
        expect(DB::table('notifications')->where('content_key', 'test.notification')->count())->toBe(0);
        expect(DB::table('notifications')->where('content_key', 'another.test.notification')->count())->toBe(1);
    });

    it('deletes both read and unread notifications', function () {
        $api = app(NotificationPublicApi::class);
        $service = app(\App\Domains\Notification\Private\Services\NotificationService::class);
        $alice = alice($this);
        $bob = bob($this);
        
        // Create notifications
        $content1 = new TestNotificationContent('unread message');
        $content2 = new TestNotificationContent('read message');
        
        $api->createNotification([$alice->id], $content1);
        $api->createNotification([$bob->id], $content2);
        
        // Get notification IDs
        $aliceNotification = DB::table('notifications')->where('content_key', 'test.notification')->first();
        $bobNotification = DB::table('notifications')->where('content_key', 'test.notification')->skip(1)->first();
        
        // Mark one as read
        $service->markAsRead($bob->id, (int) $bobNotification->id);
        
        // Verify read states
        expect(DB::table('notification_reads')->where('user_id', $alice->id)->whereNull('read_at')->count())->toBe(1);
        expect(DB::table('notification_reads')->where('user_id', $bob->id)->whereNotNull('read_at')->count())->toBe(1);
        
        // Delete all
        $count = $api->deleteNotificationsByType('test.notification');
        
        expect($count)->toBe(2);
        expect(DB::table('notifications')->where('content_key', 'test.notification')->count())->toBe(0);
    });

    it('handles multiple deletions idempotently', function () {
        $api = app(NotificationPublicApi::class);
        $alice = alice($this);
        
        $content = new TestNotificationContent('message');
        $api->createNotification([$alice->id], $content);
        
        // First deletion
        $count1 = $api->deleteNotificationsByType('test.notification');
        expect($count1)->toBe(1);
        
        // Second deletion should return 0
        $count2 = $api->deleteNotificationsByType('test.notification');
        expect($count2)->toBe(0);
    });
});

// Additional test notification content class

final class AnotherTestNotificationContent implements \App\Domains\Notification\Public\Contracts\NotificationContent
{
    public function __construct(private string $message) {}
    
    public static function type(): string
    {
        return 'another.test.notification';
    }
    
    public function toData(): array
    {
        return ['message' => $this->message];
    }
    
    public static function fromData(array $data): static
    {
        return new static(message: (string) ($data['message'] ?? ''));
    }
    
    public function display(): string
    {
        return $this->message;
    }
}
