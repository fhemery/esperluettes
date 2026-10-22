<?php

namespace App\Domains\Fanart\Public\Notifications;

class FanartAudioReplacedNotification extends FanartNotificationContent
{
    public static function type(): string
    {
        return 'fanart.audio_replaced';
    }

    protected static function langKey(): string
    {
        return 'audio_replaced';
    }
}
