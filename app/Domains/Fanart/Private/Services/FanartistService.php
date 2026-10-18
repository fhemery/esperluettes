<?php

namespace App\Domains\Fanart\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Models\FanartRoleRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FanartistService
{
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

    /** The user's fanarts are left untouched. */
    public function revoke(int $storyId, int $userId): void
    {
        Fanartist::query()
            ->where('story_id', $storyId)
            ->where('user_id', $userId)
            ->delete();
    }
}
