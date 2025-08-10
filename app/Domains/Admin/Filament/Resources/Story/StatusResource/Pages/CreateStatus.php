<?php

namespace App\Domains\Admin\Filament\Resources\Story\StatusResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\StatusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStatus extends CreateRecord
{
    protected static string $resource = StatusResource::class;
}
