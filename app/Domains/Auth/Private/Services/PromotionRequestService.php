<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Private\Models\PromotionRequest;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Api\Dto\PromotionEligibilityDto;
use App\Domains\Auth\Public\Api\Dto\PromotionRequestResultDto;
use App\Domains\Auth\Public\Api\Dto\PromotionStatusDto;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Events\PromotionAccepted;
use App\Domains\Auth\Public\Events\PromotionRejected;
use App\Domains\Auth\Public\Events\PromotionRequested;
use App\Domains\Auth\Public\Notifications\PromotionAcceptedNotification;
use App\Domains\Auth\Public\Notifications\PromotionRejectedNotification;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Carbon\Carbon;

class PromotionRequestService
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    private function eventBus(): EventBus
    {
        return app(EventBus::class);
    }

    private function notificationApi(): NotificationPublicApi
    {
        return app(NotificationPublicApi::class);
    }

    private function profileApi(): ProfilePublicApi
    {
        return app(ProfilePublicApi::class);
    }

    /**
     * Lazy resolution of ConfigPublicApi to avoid circular dependency.
     * (Auth → Config → FeatureToggle → Auth)
     */
    private function configApi(): ConfigPublicApi
    {
        return app(ConfigPublicApi::class);
    }

    /**
     * Check if a user is eligible to request promotion.
     */
    public function checkEligibility(int $userId, int $commentCount): PromotionEligibilityDto
    {
        $user = User::find($userId);

        $commentsRequired = (int) $this->configApi()->getParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN);
        $timespanSeconds = (int) $this->configApi()->getParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN);
        // Keep as float for sub-day precision (e.g., 1 hour = 0.0417 days)
        $daysRequired = $timespanSeconds / 86400;

        // Check for pending request
        $pendingRequest = $this->getPendingRequest($userId);
        $hasPendingRequest = $pendingRequest !== null;

        // Get last rejection to compute countdown start date
        $lastRejection = $this->getLastRejection($userId);
        $lastRejectionReason = $lastRejection?->rejection_reason;
        $lastRejectionDate = $lastRejection?->decided_at?->toDateTime();

        // Compute days elapsed since registration or last rejection (float for precision)
        $countdownStart = $lastRejection?->decided_at ?? ($user?->created_at ?? now());
        $daysElapsed = Carbon::parse($countdownStart)->floatDiffInDays(now());

        $meetsTime = $daysElapsed >= $daysRequired;
        $meetsComments = $commentCount >= $commentsRequired;
        $eligible = $meetsTime && $meetsComments && !$hasPendingRequest;

        return new PromotionEligibilityDto(
            eligible: $eligible,
            hasPendingRequest: $hasPendingRequest,
            daysRequired: $daysRequired,
            daysElapsed: $daysElapsed,
            commentsRequired: $commentsRequired,
            commentsPosted: $commentCount,
            lastRejectionReason: $lastRejectionReason,
            lastRejectionDate: $lastRejectionDate,
        );
    }

    /**
     * Submit a promotion request.
     */
    public function requestPromotion(int $userId, int $commentCount): PromotionRequestResultDto
    {
        $user = User::find($userId);
        if (!$user) {
            return PromotionRequestResultDto::error(PromotionRequestResultDto::ERROR_USER_NOT_FOUND);
        }

        // Check if already confirmed
        if ($user->isConfirmed()) {
            return PromotionRequestResultDto::error(PromotionRequestResultDto::ERROR_ALREADY_CONFIRMED);
        }

        // Check eligibility
        $eligibility = $this->checkEligibility($userId, $commentCount);

        if ($eligibility->hasPendingRequest) {
            return PromotionRequestResultDto::error(PromotionRequestResultDto::ERROR_ALREADY_PENDING);
        }

        if (!$eligibility->meetsTimeRequirement() || !$eligibility->meetsCommentRequirement()) {
            return PromotionRequestResultDto::error(PromotionRequestResultDto::ERROR_CRITERIA_NOT_MET);
        }

        // Create the request
        PromotionRequest::create([
            'user_id' => $userId,
            'status' => PromotionRequest::STATUS_PENDING,
            'comment_count' => $commentCount,
            'requested_at' => now(),
        ]);

        // Emit event
        $this->eventBus()->emit(new PromotionRequested($userId));

        return PromotionRequestResultDto::success();
    }

    /**
     * Get the current promotion status for a user.
     */
    public function getPromotionStatus(int $userId): PromotionStatusDto
    {
        // Check for pending request
        $pendingRequest = $this->getPendingRequest($userId);
        if ($pendingRequest !== null) {
            return PromotionStatusDto::pending();
        }

        // Check for last rejection
        $lastRejection = $this->getLastRejection($userId);
        if ($lastRejection !== null) {
            return PromotionStatusDto::rejected(
                reason: $lastRejection->rejection_reason ?? '',
                date: $lastRejection->decided_at->toDateTime(),
            );
        }

        return PromotionStatusDto::none();
    }

    /**
     * Accept a promotion request.
     */
    public function acceptRequest(int $requestId, int $decidedBy): bool
    {
        $request = PromotionRequest::find($requestId);
        if (!$request || !$request->isPending()) {
            return false;
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return false;
        }

        // Update request status
        $request->accept($decidedBy);

        // Promote user: revoke USER, grant USER_CONFIRMED
        $this->roleService->promoteToConfirmed($user, Roles::USER, Roles::USER_CONFIRMED);

        // Emit event
        $this->eventBus()->emit(new PromotionAccepted($request->user_id, $decidedBy));

        // Send notification
        $this->sendAcceptedNotification($request->user_id);

        return true;
    }

    /**
     * Reject a promotion request.
     */
    public function rejectRequest(int $requestId, int $decidedBy, string $reason): bool
    {
        $request = PromotionRequest::find($requestId);
        if (!$request || !$request->isPending()) {
            return false;
        }

        $request->reject($decidedBy, $reason);

        // Emit event
        $this->eventBus()->emit(new PromotionRejected($request->user_id, $decidedBy, $reason));

        // Send notification (without the reason - user sees it on dashboard)
        $this->sendRejectedNotification($request->user_id);

        return true;
    }

    /**
     * Send acceptance notification to user.
     */
    private function sendAcceptedNotification(int $userId): void
    {
        try {
            $profile = $this->profileApi()->getPublicProfile($userId);
            $this->notificationApi()->createNotification(
                [$userId],
                new PromotionAcceptedNotification($profile?->display_name ?? ''),
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Send rejection notification to user.
     */
    private function sendRejectedNotification(int $userId): void
    {
        try {
            $profile = $this->profileApi()->getPublicProfile($userId);
            $this->notificationApi()->createNotification(
                [$userId],
                new PromotionRejectedNotification($profile?->display_name ?? ''),
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Get count of pending promotion requests.
     */
    public function getPendingCount(): int
    {
        return PromotionRequest::where('status', PromotionRequest::STATUS_PENDING)->count();
    }

    /**
     * Get pending request for a user.
     */
    public function getPendingRequest(int $userId): ?PromotionRequest
    {
        return PromotionRequest::where('user_id', $userId)
            ->where('status', PromotionRequest::STATUS_PENDING)
            ->first();
    }

    /**
     * Get the last rejection for a user (most recent rejected request).
     */
    public function getLastRejection(int $userId): ?PromotionRequest
    {
        return PromotionRequest::where('user_id', $userId)
            ->where('status', PromotionRequest::STATUS_REJECTED)
            ->orderByDesc('decided_at')
            ->first();
    }

    /**
     * Get all promotion requests, optionally filtered.
     *
     * @param string|null $status Filter by status ('pending', 'accepted', 'rejected')
     * @param int|null $userId Filter by user ID
     * @return \Illuminate\Database\Eloquent\Collection<int, PromotionRequest>
     */
    public function getRequests(?string $status = null, ?int $userId = null)
    {
        $query = PromotionRequest::query()->orderByDesc('requested_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    /**
     * Get paginated promotion requests with optional filters.
     *
     * @param array{status?: string|null, search?: string|null} $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedRequests(array $filters, int $perPage = 20)
    {
        $query = PromotionRequest::query()
            ->join('users', 'user_promotion_request.user_id', '=', 'users.id')
            ->select('user_promotion_request.*')
            ->orderByDesc('user_promotion_request.requested_at');

        // Status filter (default to pending if not specified)
        $status = $filters['status'] ?? 'pending';
        if ($status && $status !== 'all') {
            $query->where('user_promotion_request.status', $status);
        }

        // Search filter (by user email)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('users.email', 'like', "%{$search}%");
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
