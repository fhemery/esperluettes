<?php

namespace App\Domains\Profile\Services;

use App\Domains\Auth\Models\User;
use App\Domains\Profile\Models\Profile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

class ProfileService
{
    /**
     * Get or create a profile for the given user
     */
    public function getOrCreateProfile(User $user): Profile
    {
        $profile = Profile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'slug' => $this->makeUniqueSlugForUser($user),
            ]);
        } elseif (empty($profile->slug)) {
            $this->ensureProfileSlug($profile);
        }
        
        return $profile;
    }

    /**
     * Sync profile slug when the user's name changes.
     */
    public function syncNameAndSlugForUser(int $userId, string $newName): void
    {
        $profile = $this->getOrCreateProfileByUserId($userId);

        // Compute new unique slug from the provided name
        $newSlug = $this->makeUniqueSlugForName($newName, $userId);

        if ($profile->slug !== $newSlug) {
            $profile->slug = $newSlug;
            $profile->saveQuietly();
        }
    }

    /**
     * Update profile information
     */
    public function updateProfile(User $user, array $data): Profile
    {
        $profile = $this->getOrCreateProfile($user);
        
        // Validate social network URLs
        $data = $this->validateSocialNetworkUrls($data);
        
        $profile->update($data);
        
        return $profile->fresh();
    }

    /**
     * Update profile and handle profile picture upload/removal on Save.
     */
    public function updateProfileWithPicture(User $user, array $data, ?UploadedFile $file, bool $remove): Profile
    {
        $profile = $this->getOrCreateProfile($user);

        // Validate social URLs first
        $data = $this->validateSocialNetworkUrls($data);

        // If a new file is provided, it takes precedence over removal
        if ($file instanceof UploadedFile) {
            $this->uploadProfilePicture($user, $file);
        } elseif ($remove) {
            $this->deleteProfilePicture($user);
        }

        $profile->update($data);

        return $profile->fresh();
    }

    /**
     * Upload and process profile picture
     */
    public function uploadProfilePicture(User $user, UploadedFile $file): string
    {
        $profile = $this->getOrCreateProfile($user);
        
        // Delete old profile picture if exists
        if ($profile->profile_picture_path) {
            Storage::disk('public')->delete($profile->profile_picture_path);
        }
        
        // Generate unique filename
        $filename = 'profile_pictures/' . $user->id . '_' . time() . '.jpg';
        
        // Process and save image
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file)
            ->cover(200, 200) // Square crop to 200x200
            ->toJpeg(85); // Convert to JPG with 85% quality
        
        Storage::disk('public')->put($filename, $image);
        
        // Update profile with new picture path
        $profile->update(['profile_picture_path' => $filename]);
        
        return $filename;
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePicture(User $user): bool
    {
        $profile = $this->getOrCreateProfile($user);
        
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
