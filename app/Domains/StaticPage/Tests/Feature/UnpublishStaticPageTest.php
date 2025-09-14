<?php

use App\Domains\StaticPage\Models\StaticPage;
use App\Domains\StaticPage\Services\StaticPageService;
use App\Domains\StaticPage\Events\StaticPageUnpublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Unpublishing a static page', function () {
    it('emits StaticPage.Unpublished when unpublishing a static page', function () {
        $admin = admin($this);
        $page = StaticPage::factory()->published()->create([
            'title' => 'Unpublish Me',
            'created_by' => $admin->id,
        ]);

        $service = app(StaticPageService::class);
        $service->unpublish($page);

        $event = latestEventOf(StaticPageUnpublished::name(), StaticPageUnpublished::class);
        expect($event)->not->toBeNull();
        expect($event->pageId)->toBe($page->id);
        expect($event->slug)->toBe($page->slug);
        expect($event->title)->toBe('Unpublish Me');
    });
});
