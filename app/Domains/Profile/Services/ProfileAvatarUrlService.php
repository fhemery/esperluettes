<?php

namespace App\Domains\Profile\Services;

use Illuminate\Support\Facades\Storage;

class ProfileAvatarUrlService
{
    /**
     * Build a public URL for a profile avatar given a stored relative path.
     * Falls back to a deterministic default SVG per user.
     */
    public function publicUrl(?string $relativePath, int $userId): string
    {
        $path = (string) ($relativePath ?? '');
        if ($path !== '') {
            return Storage::disk('public')->url($path);
        }
        return Storage::disk('public')->url('profile_pictures/' . $userId . '.svg');
    }
}
