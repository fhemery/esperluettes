<?php

namespace App\Domains\Admin\Filament\Resources\PinnedAnnouncementResource\Pages;

use App\Domains\Admin\Filament\Resources\PinnedAnnouncementResource;
use Filament\Resources\Pages\ListRecords;

class ReorderPinnedAnnouncements extends ListRecords
{
    protected static string $resource = PinnedAnnouncementResource::class;
}
