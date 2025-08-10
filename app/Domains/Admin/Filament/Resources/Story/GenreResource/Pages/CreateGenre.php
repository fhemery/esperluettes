<?php

namespace App\Domains\Admin\Filament\Resources\Story\GenreResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\GenreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGenre extends CreateRecord
{
    protected static string $resource = GenreResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
