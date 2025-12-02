<?php

namespace App\Domains\Notification\Private\ViewModels;

use App\Domains\Notification\Public\Services\NotificationFactory;

class NotificationPageViewModel
{
    /** @var array<int, NotificationViewModel> */
    public array $notifications = [];
    
    public bool $hasMore = false;

    /**
     * @param array<int, array{id:int,content_key:string,content_data:array|mixed,created_at:string,read_at:?string,source_user_id?:?int}> $rows
     * @param array<int, object> $profilesById A map of user_id => profile-like object exposing avatar_url
     * @param bool $hasMore Whether there are more notifications beyond this page
     */
    public static function fromRows(array $rows, array $profilesById = [], bool $hasMore = false): self
    {
        $factory = app(NotificationFactory::class);
        
        $self = new self();
        $self->notifications = array_values(array_filter(array_map(function ($r) use ($profilesById, $factory) {
            $sid = $r['source_user_id'] ?? null;
            
            // System notification when source_user_id is null
            $r['is_system'] = ($sid === null);
            
            if ($sid !== null) {
                $profile = $profilesById[(int) $sid] ?? null;
                if ($profile && isset($profile->avatar_url)) {
                    $r['avatar_url'] = $profile->avatar_url;
                }
            }
            
            // Render notification content using factory
            $content = $factory->make(
                (string) $r['content_key'],
                is_array($r['content_data']) ? $r['content_data'] : (array) $r['content_data']
            );
            
            // Discard notifications with no corresponding type
            if (!$content) {
                return null;
            }
            
            $r['rendered_content'] = $content->display();
            
            return NotificationViewModel::fromRow($r);
        }, $rows)));
        $self->hasMore = $hasMore;
        return $self;
    }
}
