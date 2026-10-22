<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartDeclinedNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.declined';
    }

    protected static function langKey(): string
    {
        return 'declined';
    }
}
