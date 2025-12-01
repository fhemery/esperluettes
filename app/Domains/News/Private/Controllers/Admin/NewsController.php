<?php

namespace App\Domains\News\Private\Controllers\Admin;

use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Requests\NewsRequest;
use App\Domains\News\Private\Services\NewsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function __construct(
        private readonly NewsService $newsService,
    ) {}

    public function index(): View
    {
        $news = News::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('news::pages.admin.news.index', [
            'news' => $news,
        ]);
    }

    public function create(): View
    {
        return view('news::pages.admin.news.create');
    }

    public function store(NewsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $news = $this->newsService->create($data);

        // If status is published, use the publish flow for events
        if (($data['status'] ?? 'draft') === 'published') {
            $this->newsService->publish($news);
        }

        return redirect()
            ->route('news.admin.index')
            ->with('success', __('news::admin.messages.created'));
    }

    public function edit(News $news): View
    {
        return view('news::pages.admin.news.edit', [
            'news' => $news,
        ]);
    }

    public function update(NewsRequest $request, News $news): RedirectResponse
    {
        $data = $request->validated();

        $wasPublished = $news->status === 'published';
        $willBePublished = ($data['status'] ?? 'draft') === 'published';

        $this->newsService->update($news, $data);

        // Handle publish/unpublish transitions
        if (!$wasPublished && $willBePublished) {
            $this->newsService->publish($news);
        } elseif ($wasPublished && !$willBePublished) {
            $this->newsService->unpublish($news);
        }

        return redirect()
            ->route('news.admin.index')
            ->with('success', __('news::admin.messages.updated'));
    }

    public function destroy(News $news): RedirectResponse
    {
        $this->newsService->delete($news);

        return redirect()
            ->route('news.admin.index')
            ->with('success', __('news::admin.messages.deleted'));
    }

    public function publish(News $news): RedirectResponse
    {
        $this->newsService->publish($news);

        return redirect()
            ->route('news.admin.index')
            ->with('success', __('news::admin.messages.published'));
    }

    public function unpublish(News $news): RedirectResponse
    {
        $this->newsService->unpublish($news);

        return redirect()
            ->route('news.admin.index')
            ->with('success', __('news::admin.messages.unpublished'));
    }
}
