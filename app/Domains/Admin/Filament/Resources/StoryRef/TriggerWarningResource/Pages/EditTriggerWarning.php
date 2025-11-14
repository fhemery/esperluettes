<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\TriggerWarningResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\TriggerWarningResource;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use Filament\Resources\Pages\EditRecord;

class EditTriggerWarning extends EditRecord
{
    protected static string $resource = TriggerWarningResource::class;

    protected function afterSave(): void
    {
        app(StoryRefPublicApi::class)->clearUiCache();
    }
}
