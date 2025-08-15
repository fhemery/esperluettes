<?php

namespace App\Domains\Home\Controllers;

use App\Domains\News\Services\NewsService;
use App\Domains\Shared\Controllers\Controller;

class HomeController extends Controller
{
    public function index(NewsService $news)
    {
        $carouselItems = $news->getPinnedForCarousel();

        return view('home::index', compact('carouselItems'));
    }
}
