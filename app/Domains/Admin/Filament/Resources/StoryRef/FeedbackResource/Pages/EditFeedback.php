<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\FeedbackResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\FeedbackResource;
use App\Domains\StoryRef\Private\Services\StoryRefLookupService;
use Filament\Resources\Pages\EditRecord;

class EditFeedback extends EditRecord
{
    protected static string $resource = FeedbackResource::class;

    protected function afterSave(): void
    {
        app(StoryRefLookupService::class)->clearCache();
    }
}
