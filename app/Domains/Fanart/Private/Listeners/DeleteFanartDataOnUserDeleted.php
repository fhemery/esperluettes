<?php

namespace App\Domains\Fanart\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Models\FanartRoleRequest;

class DeleteFanartDataOnUserDeleted
{
    public function handle(UserDeleted $event): void
    {
        // Includes rows soft-deleted by a prior deactivation. Files are left to media:gc.
        Fanart::withTrashed()->where('user_id', $event->userId)->forceDelete();
        Fanartist::where('user_id', $event->userId)->delete();
        FanartRoleRequest::where('user_id', $event->userId)->delete();
    }
}
