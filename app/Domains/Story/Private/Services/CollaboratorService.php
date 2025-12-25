<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Models\StoryCollaborator;
use App\Domains\Story\Public\Notifications\CollaboratorRoleGivenNotification;
use App\Domains\Story\Public\Notifications\CollaboratorRemovedNotification;
use App\Domains\Story\Public\Notifications\CollaboratorLeftNotification;
use Illuminate\Support\Facades\DB;

class CollaboratorService
{
    public const ROLE_AUTHOR = 'author';
    public const ROLE_BETA_READER = 'beta-reader';

    public function __construct(
        private readonly AuthPublicApi $authApi,
        private readonly ProfilePublicApi $profileApi,
        private readonly NotificationPublicApi $notifications,
    ) {}

    /**
     * Get all collaborators for a story with their profile information.
     * Authors are listed first, then other collaborators.
     *
     * @return array<int, array{user_id: int, role: string, display_name: string, avatar_url: string, slug: string}>
     */
    public function getCollaboratorsWithProfiles(Story $story): array
    {
        $collaborators = $story->collaborators()->orderByRaw("CASE WHEN role = 'author' THEN 0 ELSE 1 END")->get();
        
        $userIds = $collaborators->pluck('user_id')->all();
        $profiles = $this->profileApi->getPublicProfiles($userIds);

        $result = [];
        foreach ($collaborators as $collab) {
            $profile = $profiles[$collab->user_id] ?? null;
            $result[] = [
                'user_id' => (int) $collab->user_id,
                'role' => $collab->role,
                'display_name' => $profile?->display_name ?? 'Unknown',
                'avatar_url' => $profile?->avatar_url ?? '',
                'slug' => $profile?->slug ?? '',
            ];
        }

        return $result;
    }

    /**
     * Add a collaborator to a story.
     * Returns: 'added', 'upgraded', 'no-op', or throws exception on error.
     */
    public function addCollaborator(Story $story, int $targetUserId, string $role, int $invitedByUserId): string
    {
        $this->validateRole($role);
        $this->validateUserEligibility($targetUserId, $role);

        $existing = $story->collaborators()->where('user_id', $targetUserId)->first();

        if ($existing) {
            // Check for upgrade: beta-reader -> author
            if ($existing->role === self::ROLE_BETA_READER && $role === self::ROLE_AUTHOR) {
                DB::table('story_collaborators')
                    ->where('story_id', $story->id)
                    ->where('user_id', $targetUserId)
                    ->update([
                        'role' => self::ROLE_AUTHOR,
                        'invited_by_user_id' => $invitedByUserId,
                        'invited_at' => now(),
                        'accepted_at' => now(),
                    ]);

                // Notify target user about the role upgrade
                $this->notifyRoleGiven($story, $targetUserId, $invitedByUserId, self::ROLE_AUTHOR);

                return 'upgraded';
            }
            // Downgrade or same role = no-op
            return 'no-op';
        }

        // Create new collaborator
        DB::table('story_collaborators')->insert([
            'story_id' => $story->id,
            'user_id' => $targetUserId,
            'role' => $role,
            'invited_by_user_id' => $invitedByUserId,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        // Notify target user about the new role
        $this->notifyRoleGiven($story, $targetUserId, $invitedByUserId, $role);

        return 'added';
    }

    /**
     * Remove a collaborator from a story.
     * Only non-authors can be removed by others.
     */
    public function removeCollaborator(Story $story, int $targetUserId, int $removedByUserId): bool
    {
        $collaborator = $story->collaborators()->where('user_id', $targetUserId)->first();

        if (!$collaborator) {
            return false;
        }

        // Authors cannot be removed by others
        if ($collaborator->role === self::ROLE_AUTHOR) {
            return false;
        }

        DB::table('story_collaborators')
            ->where('story_id', $story->id)
            ->where('user_id', $targetUserId)
            ->delete();

        // Notify the removed user
        $this->notifyCollaboratorRemoved($story, $targetUserId, $removedByUserId);

        return true;
    }

    /**
     * Allow an author to leave a story voluntarily.
     * Only allowed if there are other authors.
     */
    public function leaveStory(Story $story, int $userId): bool
    {
        $collaborator = $story->collaborators()->where('user_id', $userId)->first();

        if (!$collaborator) {
            return false;
        }

        // Check if user is an author
        if ($collaborator->role !== self::ROLE_AUTHOR) {
            // Non-authors can always leave
            DB::table('story_collaborators')
                ->where('story_id', $story->id)
                ->where('user_id', $userId)
                ->delete();

            // Notify remaining authors that user left
            $this->notifyCollaboratorLeft($story, $userId);

            return true;
        }

        // Authors can only leave if there are other authors
        $authorCount = $story->authors()->count();
        if ($authorCount <= 1) {
            return false;
        }

        DB::table('story_collaborators')
            ->where('story_id', $story->id)
            ->where('user_id', $userId)
            ->delete();

        // Notify remaining authors that user left
        $this->notifyCollaboratorLeft($story, $userId);

        return true;
    }

    /**
     * Get the count of collaborators for a story.
     */
    public function getCollaboratorCount(Story $story): int
    {
        return $story->collaborators()->count();
    }

    /**
     * Check if a user is an author of a story.
     */
    public function isAuthor(Story $story, int $userId): bool
    {
        return $story->authors()->where('user_id', $userId)->exists();
    }

    /**
     * Get available roles for the collaborator dropdown.
     *
     * @return array<string, string>
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_AUTHOR => 'story::collaborators.roles.author',
            self::ROLE_BETA_READER => 'story::collaborators.roles.beta_reader',
        ];
    }

    /**
     * Validate that the role is valid.
     */
    private function validateRole(string $role): void
    {
        if (!in_array($role, [self::ROLE_AUTHOR, self::ROLE_BETA_READER], true)) {
            throw new \InvalidArgumentException("Invalid collaborator role: {$role}");
        }
    }

    /**
     * Validate that the target user is eligible for the role.
     * - Author: USER_CONFIRMED only
     * - Beta-reader: USER or USER_CONFIRMED
     */
    private function validateUserEligibility(int $userId, string $role): void
    {
        $rolesByUser = $this->authApi->getRolesByUserIds([$userId]);
        $userRoles = $rolesByUser[$userId] ?? [];
        $roleSlugs = array_map(fn($r) => $r->slug, $userRoles);

        if ($role === self::ROLE_AUTHOR) {
            if (!in_array(Roles::USER_CONFIRMED, $roleSlugs, true)) {
                throw new \InvalidArgumentException('User must be confirmed to become an author.');
            }
        } elseif ($role === self::ROLE_BETA_READER) {
            if (!in_array(Roles::USER, $roleSlugs, true) && !in_array(Roles::USER_CONFIRMED, $roleSlugs, true)) {
                throw new \InvalidArgumentException('User must be a registered user to become a beta-reader.');
            }
        }
    }

    /**
     * Notify target user that they have been given a role on a story.
     */
    private function notifyRoleGiven(Story $story, int $targetUserId, int $initiatorUserId, string $role): void
    {
        $initiatorProfile = $this->profileApi->getPublicProfile($initiatorUserId);
        if (!$initiatorProfile) {
            return;
        }

        $notification = new CollaboratorRoleGivenNotification(
            userName: $initiatorProfile->display_name,
            userSlug: $initiatorProfile->slug,
            storyTitle: (string) $story->title,
            storySlug: (string) $story->slug,
            role: $role,
        );

        $this->notifications->createNotification([$targetUserId], $notification, $initiatorUserId);
    }

    /**
     * Notify target user that they have been removed from a story.
     */
    private function notifyCollaboratorRemoved(Story $story, int $targetUserId, int $removedByUserId): void
    {
        $removerProfile = $this->profileApi->getPublicProfile($removedByUserId);
        if (!$removerProfile) {
            return;
        }

        $notification = new CollaboratorRemovedNotification(
            userName: $removerProfile->display_name,
            userSlug: $removerProfile->slug,
            storyTitle: (string) $story->title,
            storySlug: (string) $story->slug,
        );

        $this->notifications->createNotification([$targetUserId], $notification, $removedByUserId);
    }

    /**
     * Notify remaining authors that a collaborator has left the story.
     */
    private function notifyCollaboratorLeft(Story $story, int $leavingUserId): void
    {
        // Get remaining author user IDs (the leaving user has already been removed)
        $authorIds = $story->authors()->pluck('user_id')->map(fn($id) => (int)$id)->all();

        if (empty($authorIds)) {
            return;
        }

        $leavingProfile = $this->profileApi->getPublicProfile($leavingUserId);
        if (!$leavingProfile) {
            return;
        }

        $notification = new CollaboratorLeftNotification(
            userName: $leavingProfile->display_name,
            userSlug: $leavingProfile->slug,
            storyTitle: (string) $story->title,
            storySlug: (string) $story->slug,
        );

        $this->notifications->createNotification($authorIds, $notification, $leavingUserId);
    }
}
