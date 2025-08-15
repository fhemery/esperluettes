<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\AudienceResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\AudienceResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAudiences extends ListRecords
{
    protected static string $resource = AudienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
