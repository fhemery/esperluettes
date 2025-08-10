<?php

namespace App\Domains\Admin\Filament\Resources\Story\TypeResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\TypeResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListTypes extends ListRecords
{
    protected static string $resource = TypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
