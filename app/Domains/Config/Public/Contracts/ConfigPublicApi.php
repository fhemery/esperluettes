<?php

namespace App\Domains\Config\Public\Contracts;

use App\Domains\Config\Public\Services\FeatureToggleService;

class ConfigPublicApi {
    public function __construct(
        private FeatureToggleService $service,
    ) {}
    
    public function addFeatureToggle(FeatureToggle $featureToggle): void
    {
        $this->service->addFeatureToggle($featureToggle);
    }

    public function isToggleEnabled(string $featureToggleName, ?string $domain = 'config'): bool
    {
        return $this->service->isToggleEnabled($featureToggleName, $domain);
    }

    public function updateFeatureToggle(string $featureToggleName, FeatureToggleAccess $access, ?string $domain = 'config'): void
    {
        $this->service->updateFeatureToggle($featureToggleName, $access, $domain);
    }

    public function deleteFeatureToggle(string $featureToggleName, ?string $domain = 'config'): void
    {
        $this->service->deleteFeatureToggle($featureToggleName, $domain);
    }
}

