<?php

namespace App\Domains\Admin\Filament\Resources\Event\DomainEventResource\Pages;

use App\Domains\Admin\Filament\Resources\Event\DomainEventResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDomainEvent extends ViewRecord
{
    protected static string $resource = DomainEventResource::class;
}
