<?php

namespace App\Domains\Admin\Filament\Resources\Moderation\ModerationReasonResource\Pages;

use App\Domains\Admin\Filament\Resources\Moderation\ModerationReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModerationReason extends EditRecord
{
    protected static string $resource = ModerationReasonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
