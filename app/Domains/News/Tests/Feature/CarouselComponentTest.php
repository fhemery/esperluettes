<?php

use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\View\Components\Carousel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Carousel Component', function () {

    it('renders carousel with pinned items', function () {
        $admin = admin($this);
        News::factory()->published()->pinned(1)->create([
            'title' => 'First Pinned News',
            'summary' => 'First summary',
            'created_by' => $admin->id,
        ]);
        News::factory()->published()->pinned(2)->create([
            'title' => 'Second Pinned News',
            'summary' => 'Second summary',
            'created_by' => $admin->id,
        ]);

        $component = new Carousel();
        $view = $component->render();
        $html = $view->render();

        expect($html)->toContain('First Pinned News')
            ->and($html)->toContain('Second Pinned News')
            ->and($html)->toContain('First summary')
            ->and($html)->toContain('Second summary')
            ->and($html)->toContain('carousel')
            ->and($html)->toContain('aria-roledescription="carousel"');
    });

    it('does not render carousel when no pinned items exist', function () {
        $admin = admin($this);
        News::factory()->published()->create([
            'title' => 'Published but not pinned',
            'is_pinned' => false,
            'created_by' => $admin->id,
        ]);

        $component = new Carousel();
        $view = $component->render();
        $html = $view->render();

        expect($html)->toBeEmpty();
    });

    it('renders carousel in compact mode', function () {
        $admin = admin($this);
        News::factory()->published()->pinned()->create([
            'title' => 'Compact Mode News',
            'summary' => 'Compact summary',
            'created_by' => $admin->id,
        ]);

        $component = new Carousel('compact');
        $view = $component->render();
        $html = $view->render();

        expect($html)->toContain('Compact Mode News')
            ->and($html)->toContain('padding-bottom:10%')
            ->and($html)->toContain('object-cover');
    });

    it('only fetches pinned published items ordered by display_order', function () {
        $admin = admin($this);
        News::factory()->published()->pinned(1)->create([
            'title' => 'First Pinned',
            'created_by' => $admin->id,
        ]);
        News::factory()->published()->pinned(2)->create([
            'title' => 'Second Pinned',
            'created_by' => $admin->id,
        ]);
        News::factory()->pinned(3)->create([
            'title' => 'Pinned but draft',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        News::factory()->published()->create([
            'title' => 'Published but not pinned',
            'is_pinned' => false,
            'created_by' => $admin->id,
        ]);

        $component = new Carousel();
        $items = $component->items;

        expect($items)->toHaveCount(2)
            ->and($items->first()->title)->toBe('First Pinned')
            ->and($items->last()->title)->toBe('Second Pinned');
    });

    it('includes accessibility features', function () {
        $admin = admin($this);
        News::factory()->published()->pinned()->create([
            'title' => 'Accessible News',
            'summary' => 'Accessible summary',
            'created_by' => $admin->id,
        ]);

        $component = new Carousel();
        $view = $component->render();
        $html = $view->render();

        expect($html)->toContain('aria-roledescription="carousel"')
            ->and($html)->toContain('aria-label')
            ->and($html)->toContain('aria-live="polite"')
            ->and($html)->toContain('aria-hidden')
            ->and($html)->toContain('aria-current')
            ->and($html)->toContain('@keydown.left.prevent')
            ->and($html)->toContain('@keydown.right.prevent');
    });

    it('includes navigation controls', function () {
        $admin = admin($this);
        $pinnedNews = News::factory()->published()->pinned()->create([
            'title' => 'Navigation News',
            'summary' => 'Navigation summary',
            'created_by' => $admin->id,
        ]);

        $component = new Carousel();
        $view = $component->render();
        $html = $view->render();

        expect($html)->toContain('‹')
            ->and($html)->toContain('›')
            ->and($html)->toContain('aria-label')
            ->and($html)->toContain('@click');
    });

    it('includes pagination dots', function () {
        $admin = admin($this);
        $pinnedNews1 = News::factory()->published()->pinned(1)->create([
            'title' => 'News 1',
            'created_by' => $admin->id,
        ]);
        $pinnedNews2 = News::factory()->published()->pinned(2)->create([
            'title' => 'News 2',
            'created_by' => $admin->id,
        ]);

        $component = new Carousel();
        $view = $component->render();
        $html = $view->render();

        expect($html)->toContain('h-2 w-2 rounded-full')
            ->and($html)->toContain('bg-white')
            ->and($html)->toContain('aria-current');
    });


    it('includes carousel region label translation key', function () {
        $admin = admin($this);
        $pinnedNews = News::factory()->published()->pinned()->create([
            'title' => 'Translation News',
            'summary' => 'Translation summary',
            'created_by' => $admin->id,
        ]);

        $component = new Carousel();
        $view = $component->render();
        $html = $view->render();

        expect($html)->toContain('news::public.carousel.region_label');
    });
});
