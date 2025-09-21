<?php

use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\StaticPage\Public\Events\StaticPageDeleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Deleting a static page', function () {
    it('emits StaticPage.Deleted when deleting a static page', function () {
        $admin = admin($this);
        $page = StaticPage::factory()->create([
            'title' => 'Delete Me',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $id = $page->id;
        $slug = $page->slug;
        $title = $page->title;

        $page->delete();

        $event = latestEventOf(StaticPageDeleted::name(), StaticPageDeleted::class);
        expect($event)->not->toBeNull();
        expect($event->pageId)->toBe($id);
        expect($event->slug)->toBe($slug);
        expect($event->title)->toBe($title);
    });
});
