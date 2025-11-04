<?php

namespace App\Domains\Notification\Private\ViewModels;

class NotificationPageViewModel
{
    /** @var array<int, NotificationViewModel> */
    public array $notifications = [];

    /**
     * @param array<int, array{id:int,content_key:string,content_data:array|mixed,created_at:string,read_at:?string,source_user_id?:?int}> $rows
     * @param array<int, object> $profilesById A map of user_id => profile-like object exposing avatar_url
     */
    public static function fromRows(array $rows, array $profilesById = []): self
    {
        $self = new self();
        $self->notifications = array_map(function ($r) use ($profilesById) {
            $sid = $r['source_user_id'] ?? null;
            if ($sid !== null) {
                $profile = $profilesById[(int) $sid] ?? null;
                if ($profile && isset($profile->avatar_url)) {
                    $r['avatar_url'] = $profile->avatar_url;
                }
            }
            return NotificationViewModel::fromRow($r);
        }, $rows);
        return $self;
    }
}
