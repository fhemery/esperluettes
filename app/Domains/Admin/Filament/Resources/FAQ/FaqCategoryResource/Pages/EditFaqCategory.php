<?php

namespace App\Domains\Admin\Filament\Resources\FAQ\FaqCategoryResource\Pages;

use App\Domains\Admin\Filament\Resources\FAQ\FaqCategoryResource;
use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Public\Api\Dto\UpdateFaqCategoryDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFaqCategory extends EditRecord
{
    protected static string $resource = FaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function () {
                    $api = app(FaqPublicApi::class);
                    $api->deleteCategory($this->record->id);
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $api = app(FaqPublicApi::class);

        $dto = new UpdateFaqCategoryDto(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'],
            isActive: $data['is_active'],
            sortOrder: $record->sort_order, // Keep existing sort_order
        );

        $updatedDto = $api->updateCategory($record->id, $dto);

        // Return fresh model
        return FaqCategory::findOrFail($updatedDto->id);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
