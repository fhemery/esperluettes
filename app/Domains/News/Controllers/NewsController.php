<?php

namespace App\Domains\News\Controllers;

use App\Domains\News\Models\News;
use App\Domains\News\Services\NewsService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

class NewsController extends BaseController
{
    public function index(NewsService $service): View
    {
        $news = News::query()
            ->published()
            ->orderForListing()
            ->paginate(12);

        $pinned = $service->getPinnedForCarousel();

        return view('news::index', [
            'news' => $news,
            'pinned' => $pinned,
        ]);
    }

    public function show(string $slug): View
    {
        $news = News::query()
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        return view('news::show', [
            'news' => $news,
        ]);
    }
}
