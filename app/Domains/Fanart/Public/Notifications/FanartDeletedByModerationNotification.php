<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartDeletedByModerationNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.deleted_by_moderation';
    }

    protected static function langKey(): string
    {
        return 'deleted_by_moderation';
    }
}
