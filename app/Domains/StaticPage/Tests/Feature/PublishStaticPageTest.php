<?php

use App\Domains\StaticPage\Models\StaticPage;
use App\Domains\StaticPage\Services\StaticPageService;
use App\Domains\StaticPage\Events\StaticPagePublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Publishing a static page', function () {
    it('emits StaticPage.Published when publishing a static page', function () {
        $admin = admin($this);
        $page = StaticPage::factory()->create([
            'title' => 'About Us',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $admin->id,
        ]);

        $service = app(StaticPageService::class);
        $service->publish($page);

        $event = latestEventOf(StaticPagePublished::name(), StaticPagePublished::class);
        expect($event)->not->toBeNull();
        expect($event->pageId)->toBe($page->id);
        expect($event->slug)->toBe($page->slug);
        expect($event->title)->toBe('About Us');
        expect($event->publishedAt)->not->toBeNull();
    });
});
