<?php

namespace App\Domains\Profile\Private\Services;

use App\Domains\Profile\Public\Events\ProfileDisplayNameChanged;
use App\Domains\Profile\Public\Events\AvatarChanged;
use App\Domains\Profile\Public\Events\BioUpdated;
use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Profile\Private\Support\AvatarGenerator;
use App\Domains\Profile\Private\Services\ProfileCacheService;
use App\Domains\Shared\Support\SimpleSlug;
use App\Domains\Shared\Services\ImageService;
use App\Domains\Events\PublicApi\EventBus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function __construct(
        private readonly ImageService $images, 
        private readonly ProfileCacheService $cache,
        private readonly EventBus $eventBus,
    ) {
    }

    /**
     * Emit BioUpdated if any of the bio/network fields changed.
     *
     * @param array{description:mixed,facebook_url:mixed,x_url:mixed,instagram_url:mixed,youtube_url:mixed} $old
     */
    private function emitBioUpdatedIfChanged(array $old, Profile $fresh): void
    {
        $new = [
            'description' => $fresh->description,
            'facebook_url' => $fresh->facebook_url,
            'x_url' => $fresh->x_url,
            'instagram_url' => $fresh->instagram_url,
            'youtube_url' => $fresh->youtube_url,
        ];

        foreach ($new as $k => $v) {
            if (($old[$k] ?? null) !== $v) {
                $this->eventBus->emit(new BioUpdated(
                    userId: $fresh->user_id,
                    description: $new['description'],
                    facebookUrl: $new['facebook_url'],
                    xUrl: $new['x_url'],
                    instagramUrl: $new['instagram_url'],
                    youtubeUrl: $new['youtube_url'],
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

        // Validate social URLs first
        $data = $this->validateSocialNetworkUrls($data);

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
            'description' => $profile->description,
            'facebook_url' => $profile->facebook_url,
            'x_url' => $profile->x_url,
            'instagram_url' => $profile->instagram_url,
            'youtube_url' => $profile->youtube_url,
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
     * Delete profile picture
     */
    private function deleteProfilePicture(Profile $profile): bool
    {
        if ($profile->profile_picture_path) {
            Storage::disk('public')->delete($profile->profile_picture_path);
            $profile->update(['profile_picture_path' => null]);

            // Emit AvatarChanged with null path to indicate removal
            $this->eventBus->emit(new AvatarChanged(
                userId: $profile->user_id,
                profilePicturePath: null,
            ));
            return true;
        }
        
        return false;
    }

    /**
     * Validate social network URLs
     */
    private function validateSocialNetworkUrls(array $data): array
    {
        $socialNetworks = [
            'facebook_url' => ['facebook.com', 'fb.com'],
            'x_url' => ['x.com', 'twitter.com'],
            'instagram_url' => ['instagram.com'],
            'youtube_url' => ['youtube.com', 'youtu.be'],
        ];
        $domainErrorKeys = [
            'facebook_url' => __('Invalid domain for Facebook URL. Allowed domains: :domains'),
            'x_url' => __('Invalid domain for X URL. Allowed domains: :domains'),
            'instagram_url' => __('Invalid domain for Instagram URL. Allowed domains: :domains'),
            'youtube_url' => __('Invalid domain for YouTube URL. Allowed domains: :domains'),
        ];

        foreach ($socialNetworks as $field => $allowedDomains) {
            if (!empty($data[$field])) {
                $url = $data[$field];
                
                // Add https:// if no protocol specified
                if (!preg_match('/^https?:\/\//', $url)) {
                    $url = 'https://' . $url;
                }
                
                // Validate URL format
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    throw new \InvalidArgumentException("Invalid URL format for {$field}");
                }
                
                // Check if domain is allowed
                $host = parse_url($url, PHP_URL_HOST);
                $host = ltrim($host, 'www.');
                
                $isValidDomain = false;
                foreach ($allowedDomains as $domain) {
                    if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                        $isValidDomain = true;
                        break;
                    }
                }
                
                if (!$isValidDomain) {
                    $messageTemplate = $domainErrorKeys[$field] ?? __('Invalid domain. Allowed domains: :domains');
                    $message = str_replace(':domains', implode(', ', $allowedDomains), $messageTemplate);
                    throw new \InvalidArgumentException($message);
                }
                
                $data[$field] = $url;
            }
        }

        return $data;
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
     */
    public function listProfiles(?string $search = null, int $page = 1, int $perPage = 50): LengthAwarePaginator
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

        return $builder->paginate(perPage: max(1, (int) $perPage), page: max(1, (int) $page));
    }


    /**
     * Check if user can edit profile (is the owner)
     */
    public function canEditProfile(int $currentUserId, int $profile_user_id): bool
    {
        return $currentUserId === $profile_user_id;
    }

}
