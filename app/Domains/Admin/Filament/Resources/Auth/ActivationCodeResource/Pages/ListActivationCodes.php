<?php

namespace App\Domains\Admin\Filament\Resources\Auth\ActivationCodeResource\Pages;

use App\Domains\Admin\Filament\Resources\Auth\ActivationCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivationCodes extends ListRecords
{
    protected static string $resource = ActivationCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
