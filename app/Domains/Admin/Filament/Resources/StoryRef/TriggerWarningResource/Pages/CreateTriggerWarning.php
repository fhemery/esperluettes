<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\TriggerWarningResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\TriggerWarningResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTriggerWarning extends CreateRecord
{
    protected static string $resource = TriggerWarningResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
