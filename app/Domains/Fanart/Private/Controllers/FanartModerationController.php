<?php

namespace App\Domains\Fanart\Private\Controllers;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class FanartModerationController
{
    public function __construct(
        private readonly FanartService $service,
        private readonly FanartPolicy $policy,
    ) {
    }

    public function destroy(Fanart $fanart): RedirectResponse
    {
        abort_unless($this->policy->moderate(Auth::id(), $fanart), 403);

        $this->service->deleteByModerator($fanart, Auth::id());

        return back()->with('success', __('fanart::ui.delete.moderated'));
    }
}
