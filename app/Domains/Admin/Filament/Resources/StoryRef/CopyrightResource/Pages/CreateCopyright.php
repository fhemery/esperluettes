<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\CopyrightResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\CopyrightResource;
use App\Domains\StoryRef\Private\Services\StoryRefLookupService;
use Filament\Resources\Pages\CreateRecord;

class CreateCopyright extends CreateRecord
{
    protected static string $resource = CopyrightResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
