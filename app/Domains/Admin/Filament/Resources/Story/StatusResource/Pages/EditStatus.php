<?php

namespace App\Domains\Admin\Filament\Resources\Story\StatusResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\StatusResource;
use Filament\Resources\Pages\EditRecord;

class EditStatus extends EditRecord
{
    protected static string $resource = StatusResource::class;
}
