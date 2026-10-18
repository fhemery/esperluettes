<?php

namespace App\Domains\Fanart\Private\Policies;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Services\FanartistService;
use App\Domains\Fanart\Private\Services\ModeService;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartMode;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Story\Public\Api\StoryPublicApi;

/**
 * Single enforcement point for every Fanart ability. Each method takes the
 * acting user id (null for a guest).
 */
class FanartPolicy
{
    private const STAFF_ROLES = [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN];

    public function __construct(
        private readonly StoryPublicApi $stories,
        private readonly AuthPublicApi $auth,
        private readonly ModeService $modes,
        private readonly FanartistService $fanartists,
    ) {
    }

    public function seeFeature(?int $userId, int $storyId): bool
    {
        if ($userId === null) {
            return false;
        }

        $slugs = $this->slugs($userId);
        if (!in_array(Roles::USER_CONFIRMED, $slugs, true) && array_intersect($slugs, self::STAFF_ROLES) === []) {
            return false;
        }

        return $this->stories->canRead($userId, $storyId);
    }

    public function view(?int $userId, Fanart $fanart): bool
    {
        if ($userId === null || !$this->seeFeature($userId, $fanart->story_id)) {
            return false;
        }

        $isStaff = $this->isStaff($userId);
        $isAuthor = $this->stories->isAuthor($userId, $fanart->story_id);

        if ($fanart->status === FanartStatus::Pending) {
            return $isAuthor || $isStaff || $fanart->user_id === $userId;
        }

        return $isAuthor || $isStaff || $this->chapterIsPublished($fanart->chapter_id);
    }

    public function streamFile(?int $userId, Fanart $fanart): bool
    {
        return $this->view($userId, $fanart);
    }

    /** Authors are excluded: they use {@see uploadArtwork()}. */
    public function submit(?int $userId, int $storyId, FanartKind $kind): bool
    {
        return $userId !== null
            && !$this->stories->isAuthor($userId, $storyId)
            && $this->modes->canSubmit($userId, $storyId, $kind);
    }

    /** Author-only; the uploader edits their own artwork, see {@see editArtwork()}. */
    public function uploadArtwork(?int $userId, int $storyId): bool
    {
        return $userId !== null
            && $this->seeFeature($userId, $storyId)
            && $this->stories->isAuthor($userId, $storyId)
            && !$this->isPrivate($storyId);
    }

    public function editArtwork(?int $userId, Fanart $fanart): bool
    {
        return $fanart->is_artwork
            && $fanart->user_id === $userId
            && $this->uploadArtwork($userId, $fanart->story_id);
    }

    public function review(?int $userId, Fanart $fanart): bool
    {
        return $userId !== null
            && $fanart->status === FanartStatus::Pending
            && $this->seeFeature($userId, $fanart->story_id)
            && $this->stories->isAuthor($userId, $fanart->story_id);
    }

    public function delete(?int $userId, Fanart $fanart): bool
    {
        if ($userId === null || !$this->seeFeature($userId, $fanart->story_id)) {
            return false;
        }

        if ($fanart->user_id === $userId) {
            return true;
        }

        return $fanart->status === FanartStatus::Published
            && $this->stories->isAuthor($userId, $fanart->story_id);
    }

    public function moderate(?int $userId, Fanart $fanart): bool
    {
        return $userId !== null
            && $this->isStaff($userId)
            && $this->stories->canRead($userId, $fanart->story_id);
    }

    public function manageFanartists(?int $userId, int $storyId): bool
    {
        return $userId !== null
            && $this->seeFeature($userId, $storyId)
            && $this->stories->isAuthor($userId, $storyId);
    }

    public function requestRole(?int $userId, int $storyId): bool
    {
        if ($userId === null
            || !in_array(Roles::USER_CONFIRMED, $this->slugs($userId), true)
            || !$this->stories->canRead($userId, $storyId)
            || $this->stories->isAuthor($userId, $storyId)
            || $this->fanartists->isFanartist($userId, $storyId)
            || $this->fanartists->hasPendingRequest($userId, $storyId)
            || $this->fanartists->isInCooldown($userId, $storyId)) {
            return false;
        }

        foreach (FanartKind::cases() as $kind) {
            if ($this->modes->effectiveMode($storyId, $kind) === FanartMode::Designated) {
                return true;
            }
        }

        return false;
    }

    /** @return string[] */
    private function slugs(int $userId): array
    {
        return array_map(fn ($r) => $r->slug, $this->auth->getRolesByUserIds([$userId])[$userId] ?? []);
    }

    private function isStaff(int $userId): bool
    {
        return array_intersect($this->slugs($userId), self::STAFF_ROLES) !== [];
    }

    private function isPrivate(int $storyId): bool
    {
        $story = $this->stories->getStory($storyId);

        return $story === null || $story->visibility === 'private';
    }

    private function chapterIsPublished(?int $chapterId): bool
    {
        if ($chapterId === null) {
            return true;
        }

        $chapter = $this->stories->getChaptersByIds([$chapterId])[$chapterId] ?? null;

        return $chapter !== null && $chapter->status === 'published';
    }
}
