<?php

namespace App\Domains\Admin\Filament\Resources\StaticPage\StaticPageResource\Pages;

use App\Domains\Admin\Filament\Resources\StaticPage\StaticPageResource;
use App\Domains\StaticPage\Services\StaticPageService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaticPage extends EditRecord
{
    protected static string $resource = StaticPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $service = app(StaticPageService::class);

        // Sanitize HTML content
        $data['content'] = $service->sanitizeContent($data['content'] ?? '');

        // Handle header image removal
        if (!empty($data['remove_header_image'])) {
            $service->deleteHeaderImage($this->record->header_image_path ?? null);
            $data['header_image_path'] = null;
        }

        // Process header image if re-uploaded via temporary field (normalize Filament value first)
        if (!empty($data['header_image'])) {
            $fileValue = $data['header_image'];
            if (is_array($fileValue) && isset($fileValue['path'])) {
                $fileValue = $fileValue['path'];
            }
            // If replacing, delete previous variants
            if (!empty($this->record->header_image_path)) {
                $service->deleteHeaderImage($this->record->header_image_path);
            }
            $data['header_image_path'] = $service->processHeaderImage($fileValue);
        }
        unset($data['header_image'], $data['remove_header_image']);

        // Status / published_at policy
        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
