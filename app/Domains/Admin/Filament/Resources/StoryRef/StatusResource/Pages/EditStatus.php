<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\StatusResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\StatusResource;
use Filament\Resources\Pages\EditRecord;

class EditStatus extends EditRecord
{
    protected static string $resource = StatusResource::class;

    protected function afterSave(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
