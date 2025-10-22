<?php

namespace App\Domains\Admin\Filament\Resources\FAQ\FaqCategoryResource\Pages;

use App\Domains\Admin\Filament\Resources\FAQ\FaqCategoryResource;
use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqCategories extends ListRecords
{
    protected static string $resource = FaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function reorderTable(array $order): void
    {
        // Let Filament handle updating the sort_order column on the model.
        parent::reorderTable($order);
        $this->dispatch('$refresh');
    }
}
