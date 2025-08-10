<?php

namespace App\Domains\Admin\Filament\Resources\Story\AudienceResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\AudienceResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAudiences extends ListRecords
{
    protected static string $resource = AudienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
