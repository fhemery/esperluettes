<?php

namespace App\Domains\Admin\Filament\Resources\FAQ\FaqQuestionResource\Pages;

use App\Domains\Admin\Filament\Resources\FAQ\FaqQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqQuestions extends ListRecords
{
    protected static string $resource = FaqQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
