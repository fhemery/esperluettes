<?php

declare(strict_types=1);

namespace App\Domains\Story\Private\Controllers\Admin;

use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Support\AdminModerationAccessUrl;
use App\Domains\Story\Public\Events\ModeratorAccessedPrivateChapter;
use App\Domains\Story\Public\Events\ModeratorAccessedPrivateStory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class StoryModerationAdminController
{
    public function __construct(
        private readonly StoryService $storyService,
        private readonly ProfilePublicApi $profileApi,
        private readonly EventBus $eventBus,
    ) {}

    public function index(Request $request): View
    {
        $search = (string) $request->get('search', '');
        $page = (int) $request->get('page', 1);

        $stories = $this->storyService->searchStoriesForAdmin($search, 20, $page);
        $stories->appends($request->except('page'));

        $userIds = [];
        foreach ($stories as $story) {
            foreach ($story->collaborators as $collaborator) {
                $userIds[] = $collaborator->user_id;
            }
        }
        $profiles = $this->profileApi->getPublicProfiles(array_unique($userIds));

        return view('story::pages.admin.moderation.index', [
            'stories' => $stories,
            'profiles' => $profiles,
            'search' => $search,
            'moderatorId' => (int) Auth::id(),
            'adminModerationAccessUrl' => AdminModerationAccessUrl::class,
        ]);
    }

    public function chapters(Story $story): View
    {
        $story->load(['collaborators', 'chapters' => fn ($q) => $q->orderBy('sort_order')]);

        return view('story::pages.admin.moderation.partials._chapters', [
            'story' => $story,
            'chapters' => $story->chapters,
            'moderatorId' => (int) Auth::id(),
            'adminModerationAccessUrl' => AdminModerationAccessUrl::class,
        ]);
    }

    public function accessStory(Request $request): RedirectResponse
    {
        $storyId = $this->resolveEntityId($request, 'token');
        $story = Story::query()->findOrFail($storyId);

        $this->eventBus->emitSync(new ModeratorAccessedPrivateStory(
            storyId: (int) $story->id,
            title: (string) $story->title,
        ));

        return redirect()->route('stories.show', ['slug' => $story->slug]);
    }

    public function accessChapter(Request $request): RedirectResponse
    {
        $chapterId = $this->resolveEntityId($request, 'token');
        $chapter = Chapter::query()->findOrFail($chapterId);
        $chapter->loadMissing('story');

        $this->eventBus->emitSync(new ModeratorAccessedPrivateChapter(
            chapterId: (int) $chapter->id,
            title: (string) $chapter->title,
            storyId: (int) $chapter->story_id,
        ));

        return redirect()->route('chapters.show', [
            'storySlug' => $chapter->story->slug,
            'chapterSlug' => $chapter->slug,
        ]);
    }

    private function resolveEntityId(Request $request, string $tokenField): int
    {
        try {
            return (int) Crypt::decryptString((string) $request->query($tokenField));
        } catch (DecryptException) {
            abort(404);
        }
    }
}
