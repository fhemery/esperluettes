<?php

namespace App\Domains\Profile\Services;

use App\Domains\Auth\Models\User;
use App\Domains\Profile\Models\Profile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfileService
{
    /**
     * Get or create a profile for the given user
     */
    public function getOrCreateProfile(User $user): Profile
    {
        $profile = Profile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            $profile = Profile::create(['user_id' => $user->id]);
        }
        
        return $profile;
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
                    throw new \InvalidArgumentException("Invalid domain for {$field}. Allowed domains: " . implode(', ', $allowedDomains));
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
            $profile = Profile::create(['user_id' => $userId]);
            $profile->load('user'); // Load the user relationship
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
}
