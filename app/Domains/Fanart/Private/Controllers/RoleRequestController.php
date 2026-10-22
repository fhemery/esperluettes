<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Requests\RoleRequestRequest;
use App\Domains\Fanart\Private\Services\FanartistService;
use App\Domains\Fanart\Private\Support\StoryResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** `$request` is the role-request id route segment; the form request only authorizes. */
class RoleRequestController
{
    public function __construct(
        private readonly FanartistService $service,
        private readonly StoryResolver $stories,
    ) {
    }

    public function store(RoleRequestRequest $form, string $slug): RedirectResponse
    {
        $this->service->request($this->stories->resolve($slug)->id, Auth::id());

        return back()->with('success', __('fanart::ui.role_request.sent'));
    }

    public function destroy(RoleRequestRequest $form, string $slug, int $request): RedirectResponse
    {
        $story = $this->stories->resolve($slug);
        $this->service->withdraw($this->service->findPending($request, $story->id));

        return back()->with('success', __('fanart::ui.role_request.withdrawn'));
    }

    public function accept(RoleRequestRequest $form, string $slug, int $request): RedirectResponse
    {
        $story = $this->stories->resolve($slug);

        try {
            $this->service->accept($this->service->findPending($request, $story->id), Auth::id());
        } catch (ValidationException $e) {
            return back()->withErrors(['target_users' => $e->getMessage()]);
        }

        return back()->with('success', __('fanart::ui.role_request.accepted'));
    }

    public function decline(RoleRequestRequest $form, string $slug, int $request): RedirectResponse
    {
        $story = $this->stories->resolve($slug);
        $this->service->decline($this->service->findPending($request, $story->id), Auth::id());

        return back()->with('success', __('fanart::ui.role_request.declined'));
    }
}
