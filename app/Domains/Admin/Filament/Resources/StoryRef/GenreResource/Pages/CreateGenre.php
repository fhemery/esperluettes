<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\GenreResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\GenreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGenre extends CreateRecord
{
    protected static string $resource = GenreResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
