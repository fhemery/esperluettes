<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartSubmittedNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.submitted';
    }

    protected static function langKey(): string
    {
        return 'submitted';
    }
}
