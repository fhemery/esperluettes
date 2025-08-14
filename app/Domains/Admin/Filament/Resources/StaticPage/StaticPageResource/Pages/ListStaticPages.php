<?php

namespace App\Domains\Admin\Filament\Resources\StaticPage\StaticPageResource\Pages;

use App\Domains\Admin\Filament\Resources\StaticPage\StaticPageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaticPages extends ListRecords
{
    protected static string $resource = StaticPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
