<?php

namespace App\Domains\Admin\Filament\Resources\Auth\RoleResource\Pages;

use App\Domains\Admin\Filament\Resources\Auth\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
