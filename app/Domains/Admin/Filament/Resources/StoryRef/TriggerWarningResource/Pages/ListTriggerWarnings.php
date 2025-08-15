<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\TriggerWarningResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\TriggerWarningResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListTriggerWarnings extends ListRecords
{
    protected static string $resource = TriggerWarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
