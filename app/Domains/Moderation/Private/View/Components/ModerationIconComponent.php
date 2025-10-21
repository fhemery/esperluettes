<?php

declare(strict_types=1);

namespace App\Domains\Moderation\Private\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Moderation\Private\Services\ModerationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class ModerationIconComponent extends Component
{
    public int $pendingCount = 0;
    public bool $shouldDisplay = false;

    public function __construct(
        private ModerationService $moderationService,
        private AuthPublicApi $authPublicApi,
    ) {
        if (!Auth::check()) {
            return;
        }

        $isPrivileged = $this->authPublicApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR]);
        if (! $isPrivileged) {
            return;
        }

        $this->shouldDisplay = true;
        $this->pendingCount = $this->moderationService->getPendingReportsCount();
    }

    public function render()
    {
        return view('moderation::components.moderation-icon');
    }
}
