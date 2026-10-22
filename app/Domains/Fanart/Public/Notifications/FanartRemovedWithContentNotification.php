<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartRemovedWithContentNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.removed_with_content';
    }

    protected static function langKey(): string
    {
        return 'removed_with_content';
    }
}
