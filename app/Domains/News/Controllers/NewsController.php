<?php

namespace App\Domains\News\Controllers;

use App\Domains\News\Models\News;
use App\Domains\News\Services\NewsService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function show(Request $request, string $slug): View|Response
    {
        // Try published first
        $news = News::query()
            ->where('slug', $slug)
            ->published()
            ->first();

        $user = $request->user();

        if (!$news) {
            // If not published and user is admin, allow preview of draft by direct lookup
            if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                $news = News::query()->where('slug', $slug)->first();
                if (!$news) {
                    abort(404);
                }
            } else {
                abort(404);
            }
        }

        // If draft, only admins may access
        if ($news->status === 'draft') {
            if (!$user || !$user->isAdmin()) {
                abort(404);
            }
        }

        return view('news::show', [
            'news' => $news,
        ]);
    }
}
