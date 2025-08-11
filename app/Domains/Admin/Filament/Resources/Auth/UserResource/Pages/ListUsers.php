<?php

namespace App\Domains\Admin\Filament\Resources\Auth\UserResource\Pages;

use App\Domains\Admin\Filament\Resources\Auth\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
