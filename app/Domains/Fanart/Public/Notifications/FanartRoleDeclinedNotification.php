<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartRoleDeclinedNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.role_declined';
    }

    protected static function langKey(): string
    {
        return 'role_declined';
    }
}
