<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Requests\StoreFanartRequest;
use App\Domains\Fanart\Private\Requests\UpdateArtworkRequest;
use App\Domains\Story\Public\Api\StoryPublicApi;
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
        private readonly StoryPublicApi $storyApi,
    ) {
    }

    public function create(string $slug, string $kind): View
    {
        $story = $this->stories->resolve($slug);
        $userId = Auth::id();
        $kind = FanartKind::tryFrom($kind) ?? abort(404);

        $isArtwork = $this->policy->uploadArtwork($userId, $story->id);

        abort_unless($isArtwork || $this->policy->submit($userId, $story->id, $kind), 403);

        return view('fanart::pages.create', [
            'story' => $story,
            'kind' => $kind->value,
            'chapters' => $this->service->publishedChapterOptions($story->id),
            'isArtwork' => $isArtwork,
            'credits' => $isArtwork ? max(0, $this->storyApi->getAvailableChapterCredits($userId)) : null,
        ]);
    }

    public function store(StoreFanartRequest $request): RedirectResponse
    {
        $story = $this->stories->resolve((string) $request->route('slug'));
        $userId = Auth::id();

        if ($this->policy->uploadArtwork($userId, $story->id)) {
            $this->service->uploadArtwork($userId, $story->id, $request->toData());
            $message = __('fanart::ui.create.artwork_published');
        } else {
            $this->service->submit($userId, $story->id, $request->toData());
            $message = __('fanart::ui.create.submitted');
        }

        return redirect()->route('stories.show', $story->slug)->with('success', $message);
    }

    public function edit(Fanart $fanart): View
    {
        abort_unless($this->policy->editArtwork(Auth::id(), $fanart), 403);

        $story = $this->stories->byId($fanart->story_id);

        return view('fanart::pages.edit', [
            'fanart' => $fanart,
            'story' => $story,
            'chapters' => $this->service->publishedChapterOptions($fanart->story_id),
        ]);
    }

    public function destroy(Fanart $fanart): RedirectResponse
    {
        $userId = Auth::id();
        abort_unless($this->policy->delete($userId, $fanart), 403);

        if ($fanart->user_id === $userId) {
            $this->service->deleteByOwner($fanart);
        } else {
            $this->service->deleteByAuthor($fanart, $userId);
        }

        return back()->with('success', __('fanart::ui.delete.deleted'));
    }

    /** `$fanart` is declared so the route binding is resolved before the request authorizes. */
    public function update(UpdateArtworkRequest $request, Fanart $fanart): RedirectResponse
    {
        $this->service->updateArtwork($fanart, Auth::id(), $request->toData());

        return redirect()
            ->route('stories.show', $this->stories->byId($fanart->story_id)->slug)
            ->with('success', __('fanart::ui.edit.updated'));
    }
}
