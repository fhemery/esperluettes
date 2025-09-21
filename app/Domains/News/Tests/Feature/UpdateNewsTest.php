<?php

use App\Domains\News\Private\Models\News;
use App\Domains\News\Public\Events\NewsUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Updating a news item', function () {
    it('emits News.Updated when updating a news item', function () {
        $admin = admin($this);
        $news = News::factory()->create([
            'title' => 'Old Title',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $news->title = 'New Title';
        $news->summary = 'Changed summary';
        $news->save();

        $event = latestEventOf(NewsUpdated::name(), NewsUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->newsId)->toBe($news->id);
        expect($event->title)->toBe('New Title');
        expect($event->changedFields)->toContain('title');
        expect($event->changedFields)->toContain('summary');
    });
});
