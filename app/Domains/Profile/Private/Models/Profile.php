<?php

namespace App\Domains\Profile\Private\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use SoftDeletes;
    protected $table = 'profile_profiles';
    
    protected $primaryKey = 'user_id';
    
    public $incrementing = false;
    
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'slug',
        'display_name',
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
     * Transient roles attached at runtime (not persisted).
     *
     * @var array<int, \App\Domains\Auth\Public\Api\Dto\RoleDto>
     */
    public array $roles = [];

    /**
     * Use slug for route model binding and URL generation.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
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
