<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\AudienceResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\AudienceResource;
use Filament\Resources\Pages\EditRecord;

class EditAudience extends EditRecord
{
    protected static string $resource = AudienceResource::class;

    protected function afterSave(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
