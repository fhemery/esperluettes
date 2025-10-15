<?php

namespace App\Domains\Admin\Filament\Resources\Moderation\ModerationReasonResource\Pages;

use App\Domains\Admin\Filament\Resources\Moderation\ModerationReasonResource;
use App\Domains\Moderation\Private\Models\ModerationReason;
use Filament\Resources\Pages\CreateRecord;

class CreateModerationReason extends CreateRecord
{
    protected static string $resource = ModerationReasonResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-assign sort_order as last position for this topic + 1
        $maxSortOrder = ModerationReason::where('topic_key', $data['topic_key'])
            ->max('sort_order') ?? -1;
        
        $data['sort_order'] = $maxSortOrder + 1;

        return $data;
    }
}
