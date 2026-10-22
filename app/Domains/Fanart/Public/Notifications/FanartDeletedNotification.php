<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartDeletedNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.deleted';
    }

    protected static function langKey(): string
    {
        return 'deleted';
    }
}
