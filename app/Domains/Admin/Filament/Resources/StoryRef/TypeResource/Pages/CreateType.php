<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\TypeResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\TypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateType extends CreateRecord
{
    protected static string $resource = TypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
