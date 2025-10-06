<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\StatusResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\StatusResource;
use App\Domains\StoryRef\Private\Services\StoryRefLookupService;
use Filament\Resources\Pages\CreateRecord;

class CreateStatus extends CreateRecord
{
    protected static string $resource = StatusResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
