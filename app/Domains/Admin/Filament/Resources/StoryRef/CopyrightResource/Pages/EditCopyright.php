<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\CopyrightResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\CopyrightResource;
use App\Domains\StoryRef\Private\Services\StoryRefLookupService;
use Filament\Resources\Pages\EditRecord;

class EditCopyright extends EditRecord
{
    protected static string $resource = CopyrightResource::class;
    

    protected function afterSave(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
