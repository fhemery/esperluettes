<?php

namespace App\Domains\Admin\Filament\Resources\Story\GenreResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\GenreResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListGenres extends ListRecords
{
    protected static string $resource = GenreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
