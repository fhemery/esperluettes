<?php

namespace App\Domains\Admin\Filament\Resources\FAQ\FaqCategoryResource\Pages;

use App\Domains\Admin\Filament\Resources\FAQ\FaqCategoryResource;
use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqCategoryDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Filament\Resources\Pages\CreateRecord;

class CreateFaqCategory extends CreateRecord
{
    protected static string $resource = FaqCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Compute sort_order as count + 1
        $data['sort_order'] = FaqCategory::count() + 1;

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $api = app(FaqPublicApi::class);

        $dto = new CreateFaqCategoryDto(
            name: $data['name'],
            description: $data['description'] ?? null,
            isActive: $data['is_active'] ?? true,
            sortOrder: $data['sort_order'],
        );

        $createdDto = $api->createCategory($dto);

        // Return the model for Filament
        return FaqCategory::findOrFail($createdDto->id);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
