<?php

namespace App\Domains\Admin\Filament\Resources\Moderation\ModerationReportResource\Pages;

use App\Domains\Admin\Filament\Resources\Moderation\ModerationReportResource;
use App\Domains\Moderation\Public\Api\ModerationPublicApi;
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
            Actions\Action::make('delete')
                ->label('')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin::moderation.reports.actions.delete.title'))
                ->modalDescription(__('admin::moderation.reports.actions.delete.confirm'))
                ->tooltip(__('admin::moderation.reports.actions.delete.label'))
                ->action(function () {
                    app(ModerationPublicApi::class)->deleteReport($this->record->id);
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
