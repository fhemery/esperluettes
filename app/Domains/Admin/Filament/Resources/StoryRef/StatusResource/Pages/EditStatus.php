<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\StatusResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\StatusResource;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use Filament\Resources\Pages\EditRecord;

class EditStatus extends EditRecord
{
    protected static string $resource = StatusResource::class;

    protected function afterSave(): void
    {
        app(StoryRefPublicApi::class)->clearUiCache();
    }
}
