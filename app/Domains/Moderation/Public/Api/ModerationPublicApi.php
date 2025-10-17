<?php

namespace App\Domains\Moderation\Public\Api;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Moderation\Private\Services\ModerationService;
use Illuminate\Auth\Access\AuthorizationException;

class ModerationPublicApi
{
    public function __construct(
        private readonly ModerationService $service,
        private readonly AuthPublicApi $auth,
    ) {}

    /**
     * Approve a moderation report (admin, tech-admin, moderator only).
     *
     * @throws AuthorizationException
     */
    public function approveReport(int $reportId): void
    {
        $this->authorize();
        $this->service->approveReport($reportId);
    }

    /**
     * Reject a moderation report (admin, tech-admin, moderator only).
     *
     * @throws AuthorizationException
     */
    public function rejectReport(int $reportId): void
    {
        $this->authorize();
        $this->service->dismissReport($reportId);
    }

    private function authorize(): void
    {
        $ok = $this->auth->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR]);
        if (! $ok) {
            throw new AuthorizationException('You are not authorized to moderate reports.');
        }
    }
}
