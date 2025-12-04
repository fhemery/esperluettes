<?php

declare(strict_types=1);

namespace App\Domains\Auth\Private\View\Components;

use App\Domains\Auth\Private\Services\PromotionRequestService;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class PromotionIconComponent extends Component
{
    public int $pendingCount = 0;
    public bool $shouldDisplay = false;

    public function __construct(
        private PromotionRequestService $promotionService,
        private AuthPublicApi $authPublicApi,
    ) {
        if (!Auth::check()) {
            return;
        }

        $isPrivileged = $this->authPublicApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR]);
        if (!$isPrivileged) {
            return;
        }

        $this->pendingCount = $this->promotionService->getPendingCount();
        
        // Only display if there are pending requests
        $this->shouldDisplay = $this->pendingCount > 0;
    }

    public function render()
    {
        return view('auth::components.promotion-icon');
    }
}
