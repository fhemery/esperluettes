<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Requests\ManageFanartistsRequest;
use App\Domains\Fanart\Private\Services\FanartistService;
use App\Domains\Fanart\Private\Support\StoryResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FanartistController
{
    public function __construct(
        private readonly FanartistService $service,
        private readonly StoryResolver $stories,
    ) {
    }

    public function store(ManageFanartistsRequest $request, string $slug): RedirectResponse
    {
        $story = $this->stories->resolve($slug);
        $errors = [];

        foreach (array_unique(array_map('intval', $request->validated('target_users'))) as $targetId) {
            try {
                $this->service->grant($story->id, $targetId, Auth::id());
            } catch (ValidationException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            return back()->withErrors(['target_users' => $errors[0]]);
        }

        return back()->with('success', __('fanart::ui.fanartists.granted'));
    }

    public function destroy(ManageFanartistsRequest $request, string $slug, int $userId): RedirectResponse
    {
        $story = $this->stories->resolve($slug);

        $this->service->revoke($story->id, $userId);

        return back()->with('success', __('fanart::ui.fanartists.revoked'));
    }
}
