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
        'facebook_handle',
        'x_handle',
        'instagram_handle',
        'youtube_handle',
        'tiktok_handle',
        'bluesky_handle',
        'mastodon_handle',
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
     * Get all social network handles as an array
     */
    public function getSocialNetworksAttribute(): array
    {
        return [
            'facebook'  => $this->facebook_handle,
            'x'         => $this->x_handle,
            'instagram' => $this->instagram_handle,
            'youtube'   => $this->youtube_handle,
            'tiktok'    => $this->tiktok_handle,
            'bluesky'   => $this->bluesky_handle,
            'mastodon'  => $this->mastodon_handle,
        ];
    }

    /**
     * Check if profile has any social networks
     */
    public function hasSocialNetworks(): bool
    {
        return !empty($this->facebook_handle)
            || !empty($this->x_handle)
            || !empty($this->instagram_handle)
            || !empty($this->youtube_handle)
            || !empty($this->tiktok_handle)
            || !empty($this->bluesky_handle)
            || !empty($this->mastodon_handle);
    }

    /**
     * Build the full profile URL for a given social network handle.
     * Returns null if the handle is empty.
     */
    public function socialUrl(string $network): ?string
    {
        return match ($network) {
            'facebook'  => $this->facebook_handle  ? 'https://www.facebook.com/' . $this->facebook_handle  : null,
            'x'         => $this->x_handle         ? 'https://x.com/' . $this->x_handle                    : null,
            'instagram' => $this->instagram_handle ? 'https://www.instagram.com/' . $this->instagram_handle : null,
            'youtube'   => $this->youtube_handle   ? 'https://www.youtube.com/' . $this->youtube_handle     : null,
            'tiktok'    => $this->tiktok_handle    ? 'https://www.tiktok.com/@' . ltrim($this->tiktok_handle, '@') : null,
            'bluesky'   => $this->bluesky_handle   ? 'https://bsky.app/profile/' . ltrim($this->bluesky_handle, '@') : null,
            'mastodon'  => $this->mastodonUrl(),
            default     => null,
        };
    }

    /**
     * Compute Mastodon profile URL from handle @user@instance.social
     */
    private function mastodonUrl(): ?string
    {
        $handle = $this->mastodon_handle;
        if (empty($handle)) {
            return null;
        }
        // Accept @user@instance or user@instance
        $handle = ltrim($handle, '@');
        $parts = explode('@', $handle, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }
        return 'https://' . $parts[1] . '/@' . $parts[0];
    }

}
