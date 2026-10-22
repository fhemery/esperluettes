<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\FanartKind;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Lazy HTML fragment of the profile tab « Fanarts »: one story's groups for one kind. */
class FanartProfileFragmentController
{
    public function __construct(
        private readonly FanartService $service,
        private readonly FanartPolicy $policy,
    ) {
    }

    public function group(Request $request, int $userId, int $storyId): View
    {
        $viewerId = (int) Auth::id();
        abort_unless($this->policy->seeProfileTab($viewerId, $userId), 403);

        $kind = FanartKind::tryFrom((string) $request->query('kind'));
        abort_if($kind === null, 404);

        $data = $this->service->profileStoryGroups($userId, $viewerId, $storyId, $kind);
        abort_if($data === null, 403);

        return view('fanart::fragments.profile-group', $data + [
            'isOwn' => $viewerId === $userId,
            'kind' => $kind,
        ]);
    }
}
