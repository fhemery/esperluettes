<?php

namespace App\Domains\Admin\Filament\Resources\AnnouncementResource\Pages;

use App\Domains\Admin\Filament\Resources\AnnouncementResource;
use App\Domains\Announcement\Services\AnnouncementService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $service = app(AnnouncementService::class);

        // Sanitize HTML content
        $data['content'] = $service->sanitizeContent($data['content'] ?? '');

        // Process header image if re-uploaded via temporary field
        if (!empty($data['header_image'])) {
            $file = $data['header_image'];
            $data['header_image_path'] = $service->processHeaderImage($file);
        }
        unset($data['header_image']);

        // Status / published_at policy
        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
