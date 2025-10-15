<?php

namespace App\Domains\Admin\Filament\Resources\Moderation\ModerationReportResource\Pages;

use App\Domains\Admin\Filament\Resources\Moderation\ModerationReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModerationReport extends EditRecord
{
    protected static string $resource = ModerationReportResource::class;

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
