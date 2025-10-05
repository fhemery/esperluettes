<?php

namespace App\Domains\News\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsService;
use App\Domains\Shared\ViewModels\BreadcrumbViewModel;
use App\Domains\Shared\ViewModels\PageViewModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class NewsController extends BaseController
{
    public function __construct(
        private NewsService $newsService,
        private AuthPublicApi $authApi,
    ) {}
    
    public function index(): View
    {
        $news = News::query()
            ->published()
            ->orderForListing()
            ->paginate(12);

        $pinned = $this->newsService->getPinnedForCarousel();

        // Page breadcrumbs: Home/Dashboard > "Actualités" (active)
        $trail = BreadcrumbViewModel::FromHome(Auth::check());
        $trail->push(__("news::public.index.title"), null, true);

        $page = PageViewModel::make()
            ->withTitle(__("news::public.index.title"))
            ->withBreadcrumbs($trail);

        return view('news::pages.index', [
            'news' => $news,
            'pinned' => $pinned,
            'page' => $page,
        ]);
    }

    public function show(Request $request, string $slug): View|Response
    {
        // Try published first
        $news = News::query()
            ->where('slug', $slug)
            ->published()
            ->first();

        if (!$news) {
            // If not published and user is admin, allow preview of draft by direct lookup
            if ($this->authApi->hasAnyRole([Roles::ADMIN])) {
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
            if (!$this->authApi->hasAnyRole([Roles::ADMIN])) {
                abort(404);
            }
        }

        // Page breadcrumbs: Home/Dashboard > "Actualités" (link) > News title (active)
        $trail = BreadcrumbViewModel::FromHome(Auth::check());
        $trail->push(__("news::public.index.title"), route('news.index'));
        $trail->push($news->title, null, true);

        $page = PageViewModel::make()
            ->withTitle($news->title)
            ->withBreadcrumbs($trail);

        return view('news::pages.show', [
            'news' => $news,
            'page' => $page,
        ]);
    }
}
