<?php

namespace App\Domains\Admin\Filament\Resources\Announcement\PinnedAnnouncementResource\Pages;

use App\Domains\Admin\Filament\Resources\Announcement\PinnedAnnouncementResource;
use App\Domains\Announcement\Services\AnnouncementService;
use Filament\Resources\Pages\ListRecords;

class ReorderPinnedAnnouncements extends ListRecords
{
    protected static string $resource = PinnedAnnouncementResource::class;
    
    public function reorderTable(array $order): void
    {
        parent::reorderTable($order);
        app(AnnouncementService::class)->bustCarouselCache();
    }
}
