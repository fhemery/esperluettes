<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartAcceptedNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.accepted';
    }

    protected static function langKey(): string
    {
        return 'accepted';
    }
}
