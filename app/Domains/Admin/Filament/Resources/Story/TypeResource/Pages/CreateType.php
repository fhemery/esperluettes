<?php

namespace App\Domains\Admin\Filament\Resources\Story\TypeResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\TypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateType extends CreateRecord
{
    protected static string $resource = TypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
