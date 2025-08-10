<?php

namespace App\Domains\Admin\Filament\Resources\Story\FeedbackResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\FeedbackResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeedback extends CreateRecord
{
    protected static string $resource = FeedbackResource::class;
}
