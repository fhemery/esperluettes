<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartRoleRevokedNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.role_revoked';
    }

    protected static function langKey(): string
    {
        return 'role_revoked';
    }
}
