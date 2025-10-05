<?php

use App\Domains\News\Private\Models\News;
use App\Domains\Auth\Private\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('News List', function () {

    it('shows published news in index with correct link', function () {
        $admin = admin($this);
        $a = News::factory()->published()->create([
            'title' => 'Published News',
            'slug' => 'published-news',
            'created_by' => $admin->id,
        ]);

        $response = $this->get(route('news.index'));
        $response->assertOk();

        $response->assertSee('Published News');
        $response->assertSee('/news/' . $a->slug, escape: false);
    });

    it('does not show non-published news in index', function () {
        $admin = admin($this);
        News::factory()->create([
            'title' => 'Draft News',
            'slug' => 'draft-news',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $admin->id,
        ]);

        $response = $this->get(route('news.index'));
        $response->assertOk();

        $response->assertDontSee('Draft News');
    });

    describe('Breadcrumbs', function () {
        it('shows Home/Dashboard > translated "Actualités" as active on index page', function () {
            $resp = $this->get(route('news.index'));
            $resp->assertOk();

            $items = breadcrumb_items($resp);
            // Expect at least 2 items: root, list label
            expect(count($items))->toBeGreaterThanOrEqual(2);

            $last = $items[count($items) - 1];
            $this->assertNull($last['href'], 'Index breadcrumb should be non-clickable');
            $this->assertSame(__('news::public.index.title'), $last['text']);
        });
    });
});
