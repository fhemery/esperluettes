<?php

namespace App\Domains\StaticPage\Private\Controllers\Admin;

use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\StaticPage\Private\Requests\StaticPageRequest;
use App\Domains\StaticPage\Private\Services\StaticPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaticPageController
{
    public function __construct(
        private readonly StaticPageService $staticPageService,
    ) {}

    public function index(): View
    {
        $pages = StaticPage::query()
            ->orderBy('title')
            ->paginate(20);

        return view('static::pages.admin.index', compact('pages'));
    }

    public function create(): View
    {
        return view('static::pages.admin.create');
    }

    public function store(StaticPageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $page = $this->staticPageService->create($data);

        if (($data['status'] ?? 'draft') === 'published') {
            $this->staticPageService->publish($page);
        }

        return redirect()->route('static.admin.index')
            ->with('success', __('static::admin.messages.created'));
    }

    public function edit(StaticPage $staticPage): View
    {
        return view('static::pages.admin.edit', ['page' => $staticPage]);
    }

    public function update(StaticPageRequest $request, StaticPage $staticPage): RedirectResponse
    {
        $data = $request->validated();
        $wasPublished = $staticPage->status === 'published';
        $shouldPublish = ($data['status'] ?? 'draft') === 'published';

        $this->staticPageService->update($staticPage, $data);

        // Handle publish state changes
        if (!$wasPublished && $shouldPublish) {
            $this->staticPageService->publish($staticPage);
        } elseif ($wasPublished && !$shouldPublish) {
            $this->staticPageService->unpublish($staticPage);
        }

        return redirect()->route('static.admin.index')
            ->with('success', __('static::admin.messages.updated'));
    }

    public function destroy(StaticPage $staticPage): RedirectResponse
    {
        $this->staticPageService->delete($staticPage);

        return redirect()->route('static.admin.index')
            ->with('success', __('static::admin.messages.deleted'));
    }

    public function publish(StaticPage $staticPage): RedirectResponse
    {
        $this->staticPageService->publish($staticPage);

        return redirect()->route('static.admin.index')
            ->with('success', __('static::admin.messages.published'));
    }

    public function unpublish(StaticPage $staticPage): RedirectResponse
    {
        $this->staticPageService->unpublish($staticPage);

        return redirect()->route('static.admin.index')
            ->with('success', __('static::admin.messages.unpublished'));
    }
}
