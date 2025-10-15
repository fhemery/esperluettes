<?php

namespace App\Domains\StaticPage\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\StaticPage\Private\Services\StaticPageService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StaticPageController extends BaseController
{
    public function __construct(
        private StaticPageService $service,
        private AuthPublicApi $authApi
    ) {}
    
    public function show(Request $request, string $slug): View|Response
    {
        $map = $this->service->getSlugMap();
        $pageId = $map[$slug] ?? null;
        $isAdmin = $this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN]);

        if (!$pageId) {
            // Not in published map. If admin, allow draft preview by direct lookup.
            if ($isAdmin) {
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
        if ($page->status === 'draft' && !$isAdmin) {
            abort(404);
        }

        return view('static::pages.show', [
            'page' => $page,
        ]);
    }
}
