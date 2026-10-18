<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Requests\StoreFanartRequest;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\StoryResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class FanartController
{
    public function __construct(
        private readonly FanartService $service,
        private readonly FanartPolicy $policy,
        private readonly StoryResolver $stories,
    ) {
    }

    public function create(string $slug): View
    {
        $story = $this->stories->resolve($slug);
        $userId = Auth::id();

        $allowedKinds = array_values(array_filter(
            FanartKind::cases(),
            fn (FanartKind $kind) => $this->policy->submit($userId, $story->id, $kind),
        ));

        if ($allowedKinds === []) {
            abort(403);
        }

        return view('fanart::pages.create', [
            'story' => $story,
            'allowedKinds' => array_map(fn (FanartKind $k) => $k->value, $allowedKinds),
            'chapters' => $this->service->publishedChapterOptions($story->id),
        ]);
    }

    public function store(StoreFanartRequest $request): RedirectResponse
    {
        $story = $this->stories->resolve((string) $request->route('slug'));

        $this->service->submit(Auth::id(), $story->id, $request->toData());

        return redirect()
            ->route('stories.show', $story->slug)
            ->with('success', __('fanart::ui.create.submitted'));
    }
}
