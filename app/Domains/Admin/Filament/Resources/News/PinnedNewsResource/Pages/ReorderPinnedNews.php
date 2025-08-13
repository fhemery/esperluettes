<?php

namespace App\Domains\Admin\Filament\Resources\News\PinnedNewsResource\Pages;

use App\Domains\Admin\Filament\Resources\News\PinnedNewsResource;
use App\Domains\News\Services\NewsService;
use Filament\Resources\Pages\ListRecords;

class ReorderPinnedNews extends ListRecords
{
    protected static string $resource = PinnedNewsResource::class;
    
    public function reorderTable(array $order): void
    {
        parent::reorderTable($order);
        app(NewsService::class)->bustCarouselCache();
    }
}
