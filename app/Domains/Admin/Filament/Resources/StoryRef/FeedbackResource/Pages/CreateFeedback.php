<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\FeedbackResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\FeedbackResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeedback extends CreateRecord
{
    protected static string $resource = FeedbackResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
