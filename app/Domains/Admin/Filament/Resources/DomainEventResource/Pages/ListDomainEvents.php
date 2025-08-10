<?php

namespace App\Domains\Admin\Filament\Resources\DomainEventResource\Pages;

use App\Domains\Admin\Filament\Resources\DomainEventResource;
use Filament\Resources\Pages\ListRecords;

class ListDomainEvents extends ListRecords
{
    protected static string $resource = DomainEventResource::class;
}
