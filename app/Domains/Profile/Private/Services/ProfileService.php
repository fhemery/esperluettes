<?php

namespace App\Domains\Profile\Private\Services;

use App\Domains\Profile\Public\Events\ProfileDisplayNameChanged;
use App\Domains\Profile\Public\Events\AvatarChanged;
use App\Domains\Profile\Public\Events\BioUpdated;
use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Profile\Private\Support\AvatarGenerator;
use App\Domains\Profile\Private\Services\ProfileCacheService;
use App\Domains\Shared\Support\SimpleSlug;
use Illuminate\Support\Facades\Storage;
use App\Domains\Shared\Services\ImageService;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class ProfileService
{
    public function __construct(
        private readonly ImageService $images, 
        private readonly ProfileCacheService $cache,
        private readonly EventBus $eventBus,
    ) {
    }

    public function emptyAbout(Profile $profile): bool
    {
        if ($profile->description === null) {
            return false;
        }

        $profile->update(['description' => null]);
        $this->cache->forgetByUserId($profile->user_id);
        return true;
    }

    public function emptySocial(Profile $profile): bool
    {
        $fields = [
            'facebook_handle'  => null,
            'x_handle'         => null,
            'instagram_handle' => null,
            'youtube_handle'   => null,
            'tiktok_handle'    => null,
            'bluesky_handle'   => null,
            'mastodon_handle'  => null,
        ];

        $profile->update($fields);
        $this->cache->forgetByUserId($profile->user_id);
        return true;
    }

    /**
     * Emit BioUpdated if any of the bio/network fields changed.
     *
     * @param array{description:mixed,facebook_handle:mixed,x_handle:mixed,instagram_handle:mixed,youtube_handle:mixed,tiktok_handle:mixed,bluesky_handle:mixed,mastodon_handle:mixed} $old
     */
    private function emitBioUpdatedIfChanged(array $old, Profile $fresh): void
    {
        $new = [
            'description'      => $fresh->description,
            'facebook_handle'  => $fresh->facebook_handle,
            'x_handle'         => $fresh->x_handle,
            'instagram_handle' => $fresh->instagram_handle,
            'youtube_handle'   => $fresh->youtube_handle,
            'tiktok_handle'    => $fresh->tiktok_handle,
            'bluesky_handle'   => $fresh->bluesky_handle,
            'mastodon_handle'  => $fresh->mastodon_handle,
        ];

        foreach ($new as $k => $v) {
            if (($old[$k] ?? null) !== $v) {
                $this->eventBus->emit(new BioUpdated(
                    userId: $fresh->user_id,
                    description: $new['description'],
                    facebookHandle: $new['facebook_handle'],
                    xHandle: $new['x_handle'],
                    instagramHandle: $new['instagram_handle'],
                    youtubeHandle: $new['youtube_handle'],
                    tiktokHandle: $new['tiktok_handle'],
                    blueskyHandle: $new['bluesky_handle'],
                    mastodonHandle: $new['mastodon_handle'],
                ));
                break;
            }
        }
    }
    
    public function createOrInitProfileOnRegistration(int $userId, ?string $name): Profile
    {
        $display = is_string($name) ? trim($name) : '';
        if ($display === '') {
            $display = 'user-' . $userId;
        }

        $avatar = AvatarGenerator::forUser($display, 200);
        // Store the avatar in the public disk
        $avatarPath = 'profile_pictures/' . $userId . '.svg';
        Storage::disk('public')->put($avatarPath, $avatar);

        // Create only if missing; do not alter an existing profile
        $profile = Profile::firstOrCreate(
            ['user_id' => $userId],
            [   
                'display_name' => $display,
                'slug' => SimpleSlug::normalize($display),
                'profile_picture_path' => $avatarPath,
            ]
        );
        return $profile;
    }

    /**
     * Get a profile by its public slug.
     */
    public function getProfileBySlug(string $slug): ?Profile
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }
        return Profile::where('slug', $slug)->first();
    }

    /**
     * Get profile for the given user
     */
    public function getProfile(int $userId): Profile
    {
        // Try cache first
        $cached = $this->cache->getByUserId($userId);
        if ($cached instanceof Profile) {
            return $cached;
        }
        $profile = Profile::where('user_id', $userId)->first();
        $this->cache->putByUserId($userId, $profile);
        return $profile;
    }
   
    /**
     * Update profile and handle profile picture upload/removal on Save.
     */
    public function updateProfileWithPicture(int $userId, array $data, ?UploadedFile $file, bool $remove): Profile
    {
        $profile = $this->getProfile($userId);

        // Sanitize description if provided
        if (isset($data['description']) && is_string($data['description'])) {
            $data['description'] = clean($data['description'], 'strict');
        }

        // If a new file is provided, it takes precedence over removal
        if ($file instanceof UploadedFile) {
            $this->uploadProfilePicture($profile, $file);
        } elseif ($remove) {
            $this->deleteProfilePicture($profile);
        }

        // Handle display name change (mutate only; do not save yet)
        $displayChanged = null; // ['old' => string, 'new' => string] when changed
        if (array_key_exists('display_name', $data)) {
            $newDisplay = is_string($data['display_name']) ? trim($data['display_name']) : '';
            if ($newDisplay !== '') {
                $displayChanged = $this->applyDisplayName($profile, $newDisplay);
            }
            unset($data['display_name']);
        }

        // Capture old bio/network fields to detect changes
        $old = [
            'description'      => $profile->description,
            'facebook_handle'  => $profile->facebook_handle,
            'x_handle'         => $profile->x_handle,
            'instagram_handle' => $profile->instagram_handle,
            'youtube_handle'   => $profile->youtube_handle,
            'tiktok_handle'    => $profile->tiktok_handle,
            'bluesky_handle'   => $profile->bluesky_handle,
            'mastodon_handle'  => $profile->mastodon_handle,
        ];

        // Apply remaining fields, then persist once
        $profile->fill($data);
        $profile->save();

        // Dispatch event after successful save, if display changed
        if (is_array($displayChanged)) {
            $this->eventBus->emit(new ProfileDisplayNameChanged(
                $profile->user_id,
                $displayChanged['old'],
                $displayChanged['new'],
            ));
        }

        $fresh = $profile->fresh();

        // Detect bio/networks changes and emit event if any changed
        $this->emitBioUpdatedIfChanged($old, $fresh);

        // Update cache after mutations
        $this->cache->putByUserId($fresh->user_id, $fresh);
        return $fresh;
    }

    /**
     * Apply display name and new slug to the given profile without saving.
     * Returns ['old' => string, 'new' => string] if a change occurred, otherwise null.
     */
    private function applyDisplayName(Profile $profile, string $newDisplayName): ?array
    {
        $normalized = trim($newDisplayName);
        if ($normalized === '') {
            throw new \InvalidArgumentException(__('Display name cannot be empty.'));
        }

        $old = (string) ($profile->display_name ?? '');
        if ($old === $normalized) {
            return null; // No change
        }

        $profile->display_name = $normalized;
        $profile->slug = SimpleSlug::normalize($normalized);

        return ['old' => $old, 'new' => $normalized];
    }

    /**
     * Upload and process profile picture
     */
    private function uploadProfilePicture(Profile $profile, UploadedFile $file): string
    {
        
        // Delete old profile picture if exists
        if ($profile->profile_picture_path) {
            Storage::disk('public')->delete($profile->profile_picture_path);
        }
        
        // Generate unique filename
        $filename = 'profile_pictures/' . $profile->user_id . '_' . time() . '.jpg';
        
        // Process and save image using shared ImageService
        $path = $this->images->saveSquareJpg('public', $filename, $file, size: 200, quality: 85);
        
        // Update profile with new picture path
        $profile->update(['profile_picture_path' => $path]);

        // Emit AvatarChanged after successful update
        $this->eventBus->emit(new AvatarChanged(
            userId: $profile->user_id,
            profilePicturePath: $path,
        ));
        
        return $path;
    }

    /**
     * Delete profile picture and handle side-effects (event + cache).
     */
    public function deleteProfilePicture(Profile $profile): bool
    {
        if ($profile->profile_picture_path) {
            Storage::disk('public')->delete($profile->profile_picture_path);
            $profile->update(['profile_picture_path' => null]);

            // Emit AvatarChanged with null path to indicate removal
            $this->eventBus->emit(new AvatarChanged(
                userId: $profile->user_id,
                profilePicturePath: null,
            ));

            // Invalidate cache for this user
            $this->cache->forgetByUserId($profile->user_id);

            return true;
        }
        
        return false;
    }


    /**
     * Batch get profiles by user IDs with caching.
     * Returns [user_id => Profile|null]
     */
    public function getProfilesByUserIds(array $userIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($ids)) {
            return [];
        }

        $results = [];
        $missing = [];
        foreach ($ids as $id) {
            $cached = $this->cache->getByUserId($id);
            if ($cached instanceof Profile || $cached === null) {
                $results[$id] = $cached;
            } else {
                $missing[] = $id;
            }
        }

        if (!empty($missing)) {
            $profiles = Profile::query()->whereIn('user_id', $missing)->get();
            foreach ($missing as $id) {
                $profile = $profiles->firstWhere('user_id', $id);
                $this->cache->putByUserId($id, $profile);
                $results[$id] = $profile;
            }
        }

        return $results;
    }


    /**
     * List profiles with optional search and pagination (no cache, no user eager-loading).
     *
     * - Search applies to display_name and slug (case-insensitive LIKE)
     * - Results are ordered by display_name ASC
     * - By default, only includes profiles of active users
     */
    public function listProfiles(?string $search = null, int $page = 1, int $perPage = 50, bool $includeInactive = false): LengthAwarePaginator
    {
        $q = is_string($search) ? trim($search) : '';

        $builder = Profile::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";
                $query->where(function ($w) use ($like) {
                    $w->where('display_name', 'like', $like)
                      ->orWhere('slug', 'like', $like);
                });
            })
            ->orderBy('display_name');

        if($includeInactive) {
            $builder = $builder->withTrashed();
        }

        return $builder->paginate(perPage: max(1, (int) $perPage), page: max(1, (int) $page));
    }


    /**
     * Check if user can edit profile (is the owner)
     */
    public function canEditProfile(int $currentUserId, int $profile_user_id): bool
    {
        return $currentUserId === $profile_user_id;
    }

    /**
     * Delete the user's profile and related assets/cache.
     * - Deletes custom profile picture if present
     * - Deletes the default generated SVG avatar if present
     * - Deletes the profile row
     * - Clears the profile cache
     */
    public function deleteProfileByUserId(int $userId): void
    {
        $profile = Profile::where('user_id', $userId)->first();

        if ($profile) {
            if (!empty($profile->profile_picture_path)) {
                Storage::disk('public')->delete($profile->profile_picture_path);
            }
            $defaultAvatarPath = 'profile_pictures/' . $userId . '.svg';
            Storage::disk('public')->delete($defaultAvatarPath);
            $profile->delete();
        } else {
            $defaultAvatarPath = 'profile_pictures/' . $userId . '.svg';
            Storage::disk('public')->delete($defaultAvatarPath);
        }

        $this->cache->forgetByUserId($userId);
    }

    /**
     * Soft delete a user's profile and clear cache.
     */
    public function softDeleteProfileByUserId(int $userId): void
    {
        $profile = Profile::query()->where('user_id', $userId)->first();
        if ($profile) {
            $profile->delete(); // soft delete
        }
        $this->cache->forgetByUserId($userId);
    }

    /**
     * Restore a soft-deleted user's profile and clear cache.
     */
    public function restoreProfileByUserId(int $userId): void
    {
        $profile = Profile::withTrashed()->where('user_id', $userId)->first();
        if ($profile && $profile->trashed()) {
            $profile->restore();
        }
        $this->cache->forgetByUserId($userId);
    }
}
