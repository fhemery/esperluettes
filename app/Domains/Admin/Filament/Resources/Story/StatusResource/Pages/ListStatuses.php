<?php

namespace App\Domains\Admin\Filament\Resources\Story\StatusResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\StatusResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListStatuses extends ListRecords
{
    protected static string $resource = StatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
