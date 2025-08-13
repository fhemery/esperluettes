<?php

namespace App\Domains\Admin\Filament\Resources\Announcement\AnnouncementResource\Pages;

use App\Domains\Admin\Filament\Resources\Announcement\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
