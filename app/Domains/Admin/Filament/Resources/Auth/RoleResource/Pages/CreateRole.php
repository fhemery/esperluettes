<?php

namespace App\Domains\Admin\Filament\Resources\Auth\RoleResource\Pages;

use App\Domains\Admin\Filament\Resources\Auth\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
