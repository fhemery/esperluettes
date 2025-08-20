<?php

namespace App\Domains\Home\Controllers;

use App\Domains\News\PublicApi\NewsPublicApi;
use App\Domains\Shared\Controllers\Controller;

class HomeController extends Controller
{
    public function index(NewsPublicApi $news)
    {
        $carouselItems = $news->getPinnedForCarousel();

        return view('home::index', compact('carouselItems'));
    }
}
