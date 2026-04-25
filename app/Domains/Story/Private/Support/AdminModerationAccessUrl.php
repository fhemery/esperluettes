<?php

declare(strict_types=1);

namespace App\Domains\Story\Private\Support;

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

final class AdminModerationAccessUrl
{
    public static function story(Story $story, ?Carbon $expiresAt = null): string
    {
        return URL::temporarySignedRoute(
            'story.admin.moderation.story-access',
            $expiresAt ?? now()->addMinutes(10),
            ['token' => Crypt::encryptString((string) $story->id)],
        );
    }

    public static function chapter(Chapter $chapter, ?Carbon $expiresAt = null): string
    {
        return URL::temporarySignedRoute(
            'story.admin.moderation.chapter-access',
            $expiresAt ?? now()->addMinutes(10),
            ['token' => Crypt::encryptString((string) $chapter->id)],
        );
    }
}
