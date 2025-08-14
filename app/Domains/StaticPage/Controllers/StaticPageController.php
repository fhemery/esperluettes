<?php

namespace App\Domains\StaticPage\Controllers;

use App\Domains\StaticPage\Models\StaticPage;
use App\Domains\StaticPage\Services\StaticPageService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StaticPageController extends BaseController
{
    public function show(Request $request, StaticPageService $service, string $slug): View|Response
    {
        $map = $service->getSlugMap();
        $pageId = $map[$slug] ?? null;
        $user = $request->user();
        if (!$pageId) {
            // Not in published map. If admin, allow draft preview by direct lookup.
            if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                $page = StaticPage::where('slug', $slug)->first();
                if (!$page) {
                    abort(404);
                }
            } else {
                abort(404);
            }
        } else {
            $page = StaticPage::findOrFail($pageId);
        }

        // If draft, allow only admins to preview
        if ($page->status === 'draft') {
            if (!$user || !$user->isAdmin()) {
                abort(404);
            }
        }

        return view('static::show', [
            'page' => $page,
        ]);
    }
}
