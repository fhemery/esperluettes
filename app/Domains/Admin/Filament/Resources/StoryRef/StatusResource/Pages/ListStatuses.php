<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef\StatusResource\Pages;

use App\Domains\Admin\Filament\Resources\StoryRef\StatusResource;
use App\Domains\Admin\Support\Export\HasCsvExport;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListStatuses extends ListRecords
{
    use HasCsvExport;

    protected static string $resource = StatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            $this->makeExportCsvAction([
                'id' => 'ID',
                'name' => __('admin::story.shared.name'),
                'slug' => __('admin::story.shared.slug'),
                'description' => __('admin::story.shared.description'),
                'is_active' => __('admin::story.shared.active'),
                'order' => __('Order'),
                'created_at' => __('Created At'),
                'updated_at' => __('Updated At'),
            ]),
        ];
    }
}
