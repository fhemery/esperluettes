<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\GenreResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\GenreResource;
use Filament\Resources\Pages\EditRecord;

class EditGenre extends EditRecord
{
    protected static string $resource = GenreResource::class;

    protected function afterSave(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
