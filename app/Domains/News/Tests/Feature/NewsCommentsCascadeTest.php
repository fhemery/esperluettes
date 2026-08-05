<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Deleting a news article cascades to its comments', function () {
    it('deletes every comment of an article when the article is deleted', function () {
        $author = admin($this);
        $news = News::factory()->published()->create(['created_by' => $author->id]);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));
        $rootId = createComment('news', $news->id, generateDummyText(20), null);
        createComment('news', $news->id, generateDummyText(25), null);

        $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]));
        createComment('news', $news->id, 'abc', $rootId);

        expect(listComments('news', $news->id)->total)->toBe(2);

        app(NewsService::class)->delete($news);

        expect(listComments('news', $news->id)->total)->toBe(0);
        $this->assertDatabaseMissing('comments', [
            'commentable_type' => 'news',
            'commentable_id' => $news->id,
        ]);
    });

    it('leaves comments of other articles untouched', function () {
        $author = admin($this);
        $first = News::factory()->published()->create(['created_by' => $author->id]);
        $second = News::factory()->published()->create(['created_by' => $author->id]);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));
        createComment('news', $first->id, generateDummyText(20), null);
        $keptRootId = createComment('news', $second->id, generateDummyText(20), null);

        $this->actingAs(bob($this, roles: [Roles::USER_CONFIRMED]));
        createComment('news', $second->id, 'abc', $keptRootId);

        app(NewsService::class)->delete($first);

        expect(listComments('news', $first->id)->total)->toBe(0);

        $keptList = listComments('news', $second->id);
        expect($keptList->total)->toBe(1);
        expect($keptList->items[0]->id)->toBe($keptRootId);
        expect($keptList->items[0]->children)->toHaveCount(1);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'news',
            'commentable_id' => $second->id,
        ]);
    });
});
