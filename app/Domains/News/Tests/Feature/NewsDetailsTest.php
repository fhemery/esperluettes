<?php

use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('News Details Test', function () {
    it('allows access to published news via route', function () {
        $admin = admin($this);
        $news = News::factory()->create([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $response = $this->get(route('news.show', ['slug' => $news->slug]));
        $response->assertOk();
        $response->assertSee('Hello World');
    });

    it('returns 404 for draft news to guests', function () {
        $admin = admin($this);
        $news = News::factory()->create([
            'title' => 'Draft News',
            'slug' => 'draft-news',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $admin->id,
        ]);

        Auth::logout();

        $response = $this->get(route('news.show', ['slug' => $news->slug]));
        $response->assertNotFound();
    });

    it('returns 404 for draft news to non-admins', function () {
        $admin = admin($this);
        $news = News::factory()->create([
            'title' => 'Draft News',
            'slug' => 'draft-news',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $admin->id,
        ]);

        $user = alice($this);
        $this->actingAs($user);

        $response = $this->get(route('news.show', ['slug' => $news->slug]));
        $response->assertNotFound();
    });

    it('shows draft preview with banner to admins', function () {
        $admin = admin($this);
        $news = News::factory()->create([
            'title' => 'Banner Test',
            'slug' => 'banner-test',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('news.show', ['slug' => $news->slug]));
        $response->assertOk();
        $response->assertSee('news::public.draft_preview');
        $response->assertSee('Banner Test');
    });

    it('allows direct access to published news', function () {
        $admin = admin($this);
        $a = News::factory()->published()->create([
            'title' => 'Published Detail',
            'slug' => 'published-detail',
            'created_by' => $admin->id,
        ]);

        $response = $this->get(route('news.show', $a->slug));
        $response->assertOk();
        $response->assertSee('Published Detail');
    });

    it('returns 404 for direct access to non-published news', function () {
        $admin = admin($this);
        $a = News::factory()->create([
            'title' => 'Draft Detail',
            'slug' => 'draft-detail',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $admin->id,
        ]);

        $alice = alice($this);

        $response = $this->actingAs($alice)->get(route('news.show', $a->slug));
        $response->assertNotFound();
    });

    describe('Breadcrumbs', function () {
        it('shows Home/Dashboard > translated list label (link) > news title on show page', function () {
            $admin = admin($this);
            $news = News::factory()->published()->create([
                'title' => 'Crumbs Title',
                'slug' => 'crumbs-title',
                'created_by' => $admin->id,
            ]);

            $resp = $this->get(route('news.show', ['slug' => $news->slug]));
            $resp->assertOk();

            $items = breadcrumb_items($resp);
            // Expect at least 3 items: root, list, news
            expect(count($items))->toBeGreaterThanOrEqual(3);

            $indexUrl = route('news.index');

            // Find list crumb (clickable) with translated label
            $listCrumb = null;
            foreach ($items as $it) {
                if (($it['href'] ?? null) === $indexUrl) { $listCrumb = $it; break; }
            }
            $this->assertNotNull($listCrumb, 'News list breadcrumb link not found');
            $this->assertSame(__('news::public.index.title'), $listCrumb['text'] ?? null);

            // Last crumb should be the news title, non-clickable
            $last = $items[count($items) - 1];
            $this->assertNull($last['href'] ?? null, 'News title breadcrumb should be non-clickable');
            $this->assertSame('Crumbs Title', $last['text'] ?? null);
        });
    });
});
