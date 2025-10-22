<?php

namespace App\Domains\Admin\Filament\Resources\FAQ\FaqQuestionResource\Pages;

use App\Domains\Admin\Filament\Resources\FAQ\FaqQuestionResource;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqQuestionDto;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use App\Domains\Shared\Services\ImageService;
use Filament\Resources\Pages\CreateRecord;

class CreateFaqQuestion extends CreateRecord
{
    protected static string $resource = FaqQuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Process image upload
        if (!empty($data['image'])) {
            $imageService = app(ImageService::class);
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

        // Compute sort_order as count within category + 1
        $data['sort_order'] = FaqQuestion::where('faq_category_id', $data['faq_category_id'])->count() + 1;

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $api = app(FaqPublicApi::class);

        $dto = new CreateFaqQuestionDto(
            faqCategoryId: $data['faq_category_id'],
            question: $data['question'],
            answer: $data['answer'],
            imagePath: $data['image_path'] ?? null,
            imageAltText: $data['image_alt_text'] ?? null,
            isActive: $data['is_active'] ?? true,
            sortOrder: $data['sort_order'],
        );

        $createdDto = $api->createQuestion($dto);

        // Return the model for Filament
        return FaqQuestion::findOrFail($createdDto->id);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
