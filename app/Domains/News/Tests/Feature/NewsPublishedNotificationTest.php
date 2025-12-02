<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsService;
use App\Domains\News\Public\Notifications\NewsPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('News Published Notification', function () {
    it('broadcasts notification to all users when news is published', function () {
        // Create users with eligible roles
        $user1 = alice($this, [], true, [Roles::USER_CONFIRMED]);
        $user2 = bob($this, [], true, [Roles::USER_CONFIRMED]);
        $user3 = carol($this, [], true, [Roles::USER]); // Non-confirmed user

        // Create draft news
        $admin = admin($this);
        $news = News::factory()->create([
            'title' => 'Test News Article',
            'slug' => 'test-news-article',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        // Publish the news
        $service = app(NewsService::class);
        $service->publish($news);

        // Verify notification was created
        $notification = getLatestNotificationByKey(NewsPublishedNotification::type());
        expect($notification)->not->toBeNull();
        expect($notification->source_user_id)->toBeNull(); // System notification

        // Verify notification content
        $contentData = is_array($notification->content_data) 
            ? $notification->content_data 
            : json_decode($notification->content_data, true);
        expect($contentData['news_title'])->toBe('Test News Article');
        expect($contentData['news_slug'])->toBe('test-news-article');

        // Verify all eligible users received the notification
        $targetUserIds = getNotificationTargetUserIds($notification->id);
        expect($targetUserIds)->toContain($user1->id);
        expect($targetUserIds)->toContain($user2->id);
        expect($targetUserIds)->toContain($user3->id);
        expect($targetUserIds)->toContain($admin->id);
    });

    it('generates correct display content', function () {
        app()->setLocale('fr');
        
        $notification = new NewsPublishedNotification(
            newsTitle: 'New Article Title',
            newsSlug: 'new-article-title',
        );

        $display = $notification->display();

        expect($display)->toContain('New Article Title');
        expect($display)->toContain(route('news.show', ['slug' => 'new-article-title']));
        expect($display)->toContain('publiée'); // French translation
    });

    it('serializes and deserializes correctly', function () {
        $original = new NewsPublishedNotification(
            newsTitle: 'Serialization Test',
            newsSlug: 'serialization-test',
        );

        $data = $original->toData();
        $restored = NewsPublishedNotification::fromData($data);

        expect($restored->newsTitle)->toBe($original->newsTitle);
        expect($restored->newsSlug)->toBe($original->newsSlug);
        expect($restored::type())->toBe(NewsPublishedNotification::type());
    });

    it('has correct type identifier', function () {
        expect(NewsPublishedNotification::type())->toBe('news.published');
    });
});
