<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\AudienceResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\AudienceResource;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use Filament\Resources\Pages\EditRecord;

class EditAudience extends EditRecord
{
    protected static string $resource = AudienceResource::class;

    protected function afterSave(): void
    {
        app(StoryRefPublicApi::class)->clearUiCache();
    }
}
