<?php

namespace App\Domains\Admin\Filament\Resources\Moderation\ModerationReasonResource\Pages;

use App\Domains\Admin\Filament\Resources\Moderation\ModerationReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModerationReasons extends ListRecords
{
    protected static string $resource = ModerationReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
