<?php

namespace App\Domains\Admin\Filament\Resources\Event\DomainEventResource\Pages;

use App\Domains\Admin\Filament\Resources\Event\DomainEventResource;
use Filament\Resources\Pages\ListRecords;

class ListDomainEvents extends ListRecords
{
    protected static string $resource = DomainEventResource::class;
}
