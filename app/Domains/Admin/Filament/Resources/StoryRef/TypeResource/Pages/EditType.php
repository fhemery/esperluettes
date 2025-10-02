<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\TypeResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\TypeResource;
use Filament\Resources\Pages\EditRecord;

class EditType extends EditRecord
{
    protected static string $resource = TypeResource::class;

    protected function afterSave(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
