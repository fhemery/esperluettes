<?php

namespace App\Domains\Admin\Filament\Resources\RoleResource\Pages;

use App\Domains\Admin\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
