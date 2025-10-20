<?php

namespace App\Domains\Admin\Filament\Resources\Calendar\ActivitiesResource\Pages;

use App\Domains\Admin\Filament\Resources\Calendar\ActivitiesResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivitiesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
