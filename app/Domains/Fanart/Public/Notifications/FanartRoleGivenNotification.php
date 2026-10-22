<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartRoleGivenNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.role_given';
    }

    protected static function langKey(): string
    {
        return 'role_given';
    }
}
