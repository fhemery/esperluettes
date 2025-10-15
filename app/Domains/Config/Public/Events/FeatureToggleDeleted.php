<?php

namespace App\Domains\Config\Public\Events;

use App\Domains\Config\Public\Events\DTO\FeatureToggleSnapshot;
use App\Domains\Events\Public\Contracts\DomainEvent;

final class FeatureToggleDeleted implements DomainEvent
{
    
    
    public function __construct(public FeatureToggleSnapshot $featureToggle)
    {

    }

    public static function name(): string
    {
        return 'Config.FeatureToggleDeleted';
    }

    public static function version(): int
    {
        return 1;
    }

    public function toPayload(): array
    {
        return [
            'featureToggle' => $this->featureToggle,
        ];
    }

    public function summary(): string
    {
        return __('config::feature_toggle.events.deleted', [
            'name' => $this->featureToggle->name,
            'domain' => $this->featureToggle->domain,
        ]);
    }
    
    public static function fromPayload(array $payload): static
    {
        return new static(
            featureToggle: FeatureToggleSnapshot::fromPayload($payload['featureToggle']),
        );
    }
}
