<?php

namespace App\Domains\Profile\Services;

use App\Domains\Profile\Events\ProfileDisplayNameChanged;
use App\Domains\Auth\Models\User;
use App\Domains\Profile\Models\Profile;
use App\Domains\Shared\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileService
{
    public function __construct(private readonly ImageService $images)
    {
    }
    
    public function createOrInitProfileOnRegistration(int $userId, ?string $name): Profile
    {
        $display = is_string($name) ? trim($name) : '';
        if ($display === '') {
            $display = 'user-' . $userId;
        }

        // Create only if missing; do not alter an existing profile
        return Profile::firstOrCreate(
            ['user_id' => $userId],
            [
                'display_name' => $display,
                'slug' => $this->makeUniqueSlugForName($display, $userId),
            ]
        );
    }

    /**
     * Get profile for the given user
     */
    public function getProfile(int $userId): Profile
    {
        return Profile::where('user_id', $userId)->first();
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

        // Apply remaining fields, then persist once
        $profile->fill($data);
        $profile->save();

        // Dispatch event after successful save, if display changed
        if (is_array($displayChanged)) {
            event(new ProfileDisplayNameChanged(
                $profile->user_id,
                $displayChanged['old'],
                $displayChanged['new'],
                new \DateTimeImmutable('now')
            ));
        }

        return $profile->fresh();
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
        $profile->slug = $this->makeUniqueSlugForName($normalized, $profile->user_id);

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
        $this->images->saveSquareJpg('public', $filename, $file, size: 200, quality: 85);
        
        // Update profile with new picture path
        $profile->update(['profile_picture_path' => $filename]);
        
        return $filename;
    }

    /**
     * Delete profile picture
     */
    private function deleteProfilePicture(Profile $profile): bool
    {
        if ($profile->profile_picture_path) {
            Storage::disk('public')->delete($profile->profile_picture_path);
            $profile->update(['profile_picture_path' => null]);
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
     * Get profile by user ID
     */
    public function getProfileByUserId(int $userId): ?Profile
    {
        return Profile::where('user_id', $userId)->with('user')->first();
    }

    /**
     * Get profile by user ID or create if it doesn't exist
     */
    public function getOrCreateProfileByUserId(int $userId): Profile
    {
        $profile = Profile::where('user_id', $userId)->with('user')->first();
        
        if (!$profile) {
            // Fetch the user to derive slug
            $user = User::findOrFail($userId);
            $profile = Profile::create([
                'user_id' => $userId,
                'slug' => $this->makeUniqueSlugForUser($user),
            ]);
            $profile->load('user'); // Load the user relationship
        } elseif (empty($profile->slug)) {
            $this->ensureProfileSlug($profile);
            $profile->load('user');
        }
        
        return $profile;
    }

    /**
     * Check if user can edit profile (is the owner)
     */
    public function canEditProfile(User $currentUser, Profile $profile): bool
    {
        return $currentUser->id === $profile->user_id;
    }

    /**
     * Ensure a profile has a unique slug. If missing, generate and save it.
     */
    public function ensureProfileSlug(Profile $profile): void
    {
        if (!empty($profile->slug)) {
            return;
        }
        $user = $profile->user ?: User::find($profile->user_id);
        $profile->slug = $this->makeUniqueSlugForUser($user);
        $profile->saveQuietly();
    }

    /**
     * Make a unique slug from the user's name with fallback to user-<id>.
     */
    private function makeUniqueSlugForUser(User $user): string
    {
        $base = Str::slug($user->name ?? '') ?: 'user-' . $user->id;
        $slug = $base;
        $i = 0;
        while (
            Profile::where('slug', $slug)
                ->where('user_id', '!=', $user->id)
                ->exists()
        ) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }

    /**
     * Make a unique slug from an arbitrary name for the given user ID.
     */
    private function makeUniqueSlugForName(string $name, int $userId): string
    {
        $base = Str::slug($name) ?: 'user-' . $userId;
        $slug = $base;
        $i = 0;
        while (
            Profile::where('slug', $slug)
                ->where('user_id', '!=', $userId)
                ->exists()
        ) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }
}
