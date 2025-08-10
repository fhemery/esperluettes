<?php

namespace App\Domains\Admin\Filament\Resources\Story\CopyrightResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\CopyrightResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListCopyrights extends ListRecords
{
    protected static string $resource = CopyrightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
