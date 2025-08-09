<?php

namespace App\Domains\Profile\Models;

use App\Domains\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $table = 'profile_profiles';
    
    protected $primaryKey = 'user_id';
    
    public $incrementing = false;
    
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'slug',
        'profile_picture_path',
        'facebook_url',
        'x_url',
        'instagram_url',
        'youtube_url',
        'description',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * Use slug for route model binding and URL generation.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the user that owns the profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the profile picture URL or default avatar
     */
    public function getProfilePictureUrlAttribute(): string
    {
        if ($this->profile_picture_path) {
            return asset('storage/' . $this->profile_picture_path);
        }
        
        return $this->getDefaultAvatarUrl();
    }

    /**
     * Generate default avatar URL based on user initials
     */
    public function getDefaultAvatarUrl(): string
    {
        $initials = $this->getInitials();
        return "https://ui-avatars.com/api/?name={$initials}&color=7F9CF5&background=EBF4FF&size=200";
    }

    /**
     * Get user initials for default avatar
     */
    private function getInitials(): string
    {
        $name = $this->user->name ?? 'User';
        $words = explode(' ', trim($name));
        
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        
        return strtoupper(substr($name, 0, 2));
    }

    /**
     * Check if profile has a custom profile picture
     */
    public function hasCustomProfilePicture(): bool
    {
        return !empty($this->profile_picture_path);
    }

    /**
     * Get all social network URLs as an array
     */
    public function getSocialNetworksAttribute(): array
    {
        return [
            'facebook' => $this->facebook_url,
            'x' => $this->x_url,
            'instagram' => $this->instagram_url,
            'youtube' => $this->youtube_url,
        ];
    }

    /**
     * Check if profile has any social networks
     */
    public function hasSocialNetworks(): bool
    {
        return !empty($this->facebook_url) || 
               !empty($this->x_url) || 
               !empty($this->instagram_url) || 
               !empty($this->youtube_url);
    }
}
