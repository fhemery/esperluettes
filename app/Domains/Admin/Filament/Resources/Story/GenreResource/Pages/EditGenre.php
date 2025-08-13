<?php

namespace App\Domains\Admin\Filament\Resources\Story\GenreResource\Pages;

use App\Domains\Admin\Filament\Resources\Story\GenreResource;
use Filament\Resources\Pages\EditRecord;

class EditGenre extends EditRecord
{
    protected static string $resource = GenreResource::class;
}
