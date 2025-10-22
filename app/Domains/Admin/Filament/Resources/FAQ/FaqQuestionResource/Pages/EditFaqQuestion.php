<?php

namespace App\Domains\Admin\Filament\Resources\FAQ\FaqQuestionResource\Pages;

use App\Domains\Admin\Filament\Resources\FAQ\FaqQuestionResource;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use App\Domains\FAQ\Public\Api\Dto\UpdateFaqQuestionDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use App\Domains\Shared\Services\ImageService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFaqQuestion extends EditRecord
{
    protected static string $resource = FaqQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function () {
                    // Delete old image if exists
                    if ($this->record->image_path) {
                        $imageService = app(ImageService::class);
                        $imageService->deleteWithVariants('public', $this->record->image_path);
                    }
                    
                    $api = app(FaqPublicApi::class);
                    $api->deleteQuestion($this->record->id);
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Don't fill the remove_image toggle
        $data['remove_image'] = false;
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $imageService = app(ImageService::class);

        // Handle image removal
        if (!empty($data['remove_image']) && $this->record->image_path) {
            $imageService->deleteWithVariants('public', $this->record->image_path);
            $data['image_path'] = null;
            $data['image_alt_text'] = null;
        }

        // Handle new image upload
        if (!empty($data['image'])) {
            // Delete old image if exists
            if ($this->record->image_path) {
                $imageService->deleteWithVariants('public', $this->record->image_path);
            }

            $fileValue = $data['image'];
            
            // Normalize FileUpload value
            if (is_array($fileValue) && isset($fileValue['path'])) {
                $fileValue = $fileValue['path'];
            }
            
            $disk = 'public';
            $folder = 'faq/' . date('Y/m');
            $data['image_path'] = $imageService->process($disk, $folder, $fileValue, widths: [400, 800]);
        }

        unset($data['image']);
        unset($data['remove_image']);

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $api = app(FaqPublicApi::class);

        $dto = new UpdateFaqQuestionDto(
            faqCategoryId: $data['faq_category_id'],
            question: $data['question'],
            slug: $data['slug'],
            answer: $data['answer'],
            imagePath: $data['image_path'] ?? null,
            imageAltText: $data['image_alt_text'] ?? null,
            isActive: $data['is_active'],
            sortOrder: $record->sort_order, // Keep existing sort_order
        );

        $updatedDto = $api->updateQuestion($record->id, $dto);

        // Return fresh model
        return FaqQuestion::findOrFail($updatedDto->id);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
