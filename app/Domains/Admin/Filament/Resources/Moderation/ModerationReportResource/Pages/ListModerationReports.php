<?php

namespace App\Domains\Admin\Filament\Resources\Moderation\ModerationReportResource\Pages;

use App\Domains\Admin\Filament\Resources\Moderation\ModerationReportResource;
use Filament\Resources\Pages\ListRecords;

class ListModerationReports extends ListRecords
{
    protected static string $resource = ModerationReportResource::class;
}
