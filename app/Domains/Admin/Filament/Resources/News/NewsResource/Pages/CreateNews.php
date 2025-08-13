<?php

namespace App\Domains\Admin\Filament\Resources\News\NewsResource\Pages;

use App\Domains\Admin\Filament\Resources\News\NewsResource;
use App\Domains\News\Services\NewsService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNews extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = app(NewsService::class);

        // Sanitize HTML content
        $data['content'] = $service->sanitizeContent($data['content'] ?? '');

        // Normalize FileUpload value: can be UploadedFile, string path (disk), or array with 'path'
        if (!empty($data['header_image'])) {
            $fileValue = $data['header_image'];
            if (is_array($fileValue) && isset($fileValue['path'])) {
                $fileValue = $fileValue['path'];
            }
            $data['header_image_path'] = $service->processHeaderImage($fileValue);
        }
        unset($data['header_image']);

        // Status / published_at policy
        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // Creator tracking
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
