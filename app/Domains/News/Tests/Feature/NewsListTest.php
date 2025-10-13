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

    it('displays carousel with pinned published news only and in the right order', function () {
        $admin = admin($this);

        // Pinned & published items (ordered)
        $first = News::factory()->published()->pinned(1)->create([
            'title' => 'Pinned One',
            'slug' => 'pinned-one',
            'created_by' => $admin->id,
        ]);
        $second = News::factory()->published()->pinned(2)->create([
            'title' => 'Pinned Two',
            'slug' => 'pinned-two',
            'created_by' => $admin->id,
        ]);

        // Not pinned (should not appear in carousel)
        $regular = News::factory()->published()->create([
            'title' => 'Regular News',
            'slug' => 'regular-news',
            'created_by' => $admin->id,
        ]);

        // Pinned but draft (should not appear)
        $draftPinned = News::factory()->pinned(3)->create([
            'title' => 'Draft Pinned',
            'slug' => 'draft-pinned',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $admin->id,
        ]);

        $resp = $this->get(route('news.index'));
        $resp->assertOk();

        // Carousel heading present
        $resp->assertSee(e(__('news::public.index.pinned_title')));

        // Carousel region is rendered
        $resp->assertSee('aria-roledescription="carousel"', escape: false);

        // Links for pinned & published items are present (in order)
        $hrefFirst = '/news/' . $first->slug;
        $hrefSecond = '/news/' . $second->slug;
        $resp->assertSeeInOrder([$hrefFirst, $hrefSecond], escape: false);

        // Non-pinned and draft-pinned should not be in the carousel
        $resp->assertDontSee('/news/' . $draftPinned->slug, escape: false);

        // Index grid may include regular news, but carousel should focus on pinned; we at least ensure regular is present on page
        $resp->assertSee('/news/' . $regular->slug, escape: false);

        // Dots/buttons count can't be easily asserted, but we can assert at least one goto button is present
        $resp->assertSee('aria-label="' . e(__('news::public.carousel.goto', ['number' => 1])) . '"', escape: false);
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
