<?php

namespace App\Domains\Notification\Private\ViewModels;

class NotificationPageViewModel
{
    /** @var array<int, NotificationViewModel> */
    public array $notifications = [];

    /**
     * @param array<int, array{id:int,content_key:string,content_data:array|mixed,created_at:string,read_at:?string}> $rows
     */
    public static function fromRows(array $rows): self
    {
        $self = new self();
        $self->notifications = array_map(fn ($r) => NotificationViewModel::fromRow($r), $rows);
        return $self;
    }
}
