<?php

use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsService;
use App\Domains\News\Public\Events\NewsUnpublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Unpublishing a news item', function () {
    it('emits News.Unpublished when unpublishing a news item', function () {
        $admin = admin($this);
        $news = News::factory()->published()->create([
            'title' => 'Unpublish Me',
            'created_by' => $admin->id,
        ]);

        $service = app(NewsService::class);
        $service->unpublish($news);

        $event = latestEventOf(NewsUnpublished::name(), NewsUnpublished::class);
        expect($event)->not->toBeNull();
        expect($event->newsId)->toBe($news->id);
        expect($event->slug)->toBe($news->slug);
        expect($event->title)->toBe('Unpublish Me');
    });
});
