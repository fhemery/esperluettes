<?php

namespace App\Domains\Admin\Filament\Resources\Story\CopyrightResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\CopyrightResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCopyright extends CreateRecord
{
    protected static string $resource = CopyrightResource::class;
}
