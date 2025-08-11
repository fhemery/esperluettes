<?php

namespace App\Domains\Admin\Filament\Resources\Auth\UserResource\Pages;

use App\Domains\Admin\Filament\Resources\Auth\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
