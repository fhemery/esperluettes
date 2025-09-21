<?php

use App\Domains\News\Private\Models\News;
use App\Domains\News\Public\Events\NewsDeleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Deleting a news item', function () {
    it('emits News.Deleted when deleting a news item', function () {
        $admin = admin($this);
        $news = News::factory()->create([
            'title' => 'Delete Me',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $id = $news->id;
        $slug = $news->slug;
        $title = $news->title;

        $news->delete();

        $event = latestEventOf(NewsDeleted::name(), NewsDeleted::class);
        expect($event)->not->toBeNull();
        expect($event->newsId)->toBe($id);
        expect($event->slug)->toBe($slug);
        expect($event->title)->toBe($title);
    });
});
