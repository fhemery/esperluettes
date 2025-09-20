<?php

use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsService;
use App\Domains\News\Public\Events\NewsPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Publishing a news item', function () {
    it('emits News.Published when publishing a news item', function () {
        $admin = admin($this);
        $news = News::factory()->create([
            'title' => 'Publish Me',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $admin->id,
        ]);

        $service = app(NewsService::class);
        $service->publish($news);

        $event = latestEventOf(NewsPublished::name(), NewsPublished::class);
        expect($event)->not->toBeNull();
        expect($event->newsId)->toBe($news->id);
        expect($event->slug)->toBe($news->slug);
        expect($event->title)->toBe('Publish Me');
    });
});
