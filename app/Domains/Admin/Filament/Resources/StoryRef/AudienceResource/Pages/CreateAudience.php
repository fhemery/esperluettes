<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\AudienceResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\AudienceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAudience extends CreateRecord
{
    protected static string $resource = AudienceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
