<?php

namespace App\Domains\Fanart\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Models\FanartRoleRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FanartistService
{
    private const REQUEST_COOLDOWN_DAYS = 30;

    public function __construct(
        private readonly AuthPublicApi $auth,
        private readonly FanartNotifier $notifier,
    ) {
    }

    public function isFanartist(int $userId, int $storyId): bool
    {
        return Fanartist::query()
            ->where('story_id', $storyId)
            ->where('user_id', $userId)
            ->exists();
    }

    /** @return int[] */
    public function listFanartistIds(int $storyId): array
    {
        return Fanartist::query()
            ->where('story_id', $storyId)
            ->orderBy('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Idempotent. Drops the user's pending request for the story and tells them,
     * but only when the role is actually new.
     *
     * @throws ValidationException when the target is not a confirmed user
     */
    public function grant(int $storyId, int $userId, int $grantedBy): void
    {
        $slugs = array_map(fn ($r) => $r->slug, $this->auth->getRolesByUserIds([$userId])[$userId] ?? []);
        if (!in_array(Roles::USER_CONFIRMED, $slugs, true)) {
            throw ValidationException::withMessages(['target_users' => __('fanart::ui.fanartists.not_confirmed')]);
        }

        $created = DB::transaction(function () use ($storyId, $userId, $grantedBy) {
            FanartRoleRequest::query()
                ->where('story_id', $storyId)
                ->where('user_id', $userId)
                ->where('status', FanartRoleRequest::STATUS_PENDING)
                ->delete();

            if ($this->isFanartist($userId, $storyId)) {
                return false;
            }

            Fanartist::create([
                'story_id' => $storyId,
                'user_id' => $userId,
                'granted_by_user_id' => $grantedBy,
            ]);

            return true;
        });

        if ($created) {
            $this->notifier->fanartRoleGiven($storyId, $userId, $grantedBy);
        }
    }

    /** @return Collection<int, FanartRoleRequest> */
    public function pendingRequests(int $storyId): Collection
    {
        return FanartRoleRequest::query()
            ->where('story_id', $storyId)
            ->where('status', FanartRoleRequest::STATUS_PENDING)
            ->orderBy('id')
            ->get();
    }

    /** A pending request of the story, 404 otherwise. */
    public function findPending(int $requestId, int $storyId): FanartRoleRequest
    {
        return FanartRoleRequest::query()
            ->where('story_id', $storyId)
            ->where('status', FanartRoleRequest::STATUS_PENDING)
            ->findOrFail($requestId);
    }

    public function hasPendingRequest(int $userId, int $storyId): bool
    {
        return $this->pendingRequest($userId, $storyId) !== null;
    }

    public function pendingRequest(int $userId, int $storyId): ?FanartRoleRequest
    {
        return FanartRoleRequest::query()
            ->where('story_id', $storyId)
            ->where('user_id', $userId)
            ->where('status', FanartRoleRequest::STATUS_PENDING)
            ->first();
    }

    /** True while the latest decline of this (user, story) is younger than the cooldown. */
    public function isInCooldown(int $userId, int $storyId): bool
    {
        $lastDecline = FanartRoleRequest::query()
            ->where('story_id', $storyId)
            ->where('user_id', $userId)
            ->where('status', FanartRoleRequest::STATUS_DECLINED)
            ->max('decided_at');

        return $lastDecline !== null
            && Carbon::parse($lastDecline)->addDays(self::REQUEST_COOLDOWN_DAYS)->isFuture();
    }

    /** Eligibility is the caller's job ({@see \App\Domains\Fanart\Private\Policies\FanartPolicy::requestRole()}). */
    public function request(int $storyId, int $userId): FanartRoleRequest
    {
        $request = FanartRoleRequest::create([
            'story_id' => $storyId,
            'user_id' => $userId,
            'status' => FanartRoleRequest::STATUS_PENDING,
        ]);

        $this->notifier->fanartRoleRequested($storyId, $userId);

        return $request;
    }

    public function withdraw(FanartRoleRequest $request): void
    {
        $request->delete();
    }

    public function accept(FanartRoleRequest $request, int $actorId): void
    {
        $this->grant($request->story_id, $request->user_id, $actorId);
    }

    public function decline(FanartRoleRequest $request, int $actorId): void
    {
        $request->update([
            'status' => FanartRoleRequest::STATUS_DECLINED,
            'decided_by_user_id' => $actorId,
            'decided_at' => now(),
        ]);

        $this->notifier->fanartRoleDeclined($request->story_id, $request->user_id, $actorId);
    }

    /** The user's fanarts are left untouched. */
    public function revoke(int $storyId, int $userId): void
    {
        Fanartist::query()
            ->where('story_id', $storyId)
            ->where('user_id', $userId)
            ->delete();
    }
}
