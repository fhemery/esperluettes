<?php

use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\StaticPage\Public\Events\StaticPageUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Updating a static page', function () {
    it('emits StaticPage.Updated when updating a static page', function () {
        $admin = admin($this);
        $page = StaticPage::factory()->create([
            'title' => 'Old Title',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $page->title = 'New Title';
        $page->summary = 'Changed summary';
        $page->save();

        $event = latestEventOf(StaticPageUpdated::name(), StaticPageUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->pageId)->toBe($page->id);
        expect($event->title)->toBe('New Title');
        expect($event->changedFields)->toContain('title');
        expect($event->changedFields)->toContain('summary');
    });
});
