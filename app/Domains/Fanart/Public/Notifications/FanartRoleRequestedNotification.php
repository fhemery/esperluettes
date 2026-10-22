<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartRoleRequestedNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.role_requested';
    }

    protected static function langKey(): string
    {
        return 'role_requested';
    }
}
