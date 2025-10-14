<?php

namespace App\Domains\Admin\Filament\Resources\Config\FeatureToggleResource\Pages;

use App\Domains\Admin\Filament\Resources\Config\FeatureToggleResource;
use Filament\Resources\Pages\ListRecords;

class ListFeatureToggles extends ListRecords
{
    protected static string $resource = FeatureToggleResource::class;
}
