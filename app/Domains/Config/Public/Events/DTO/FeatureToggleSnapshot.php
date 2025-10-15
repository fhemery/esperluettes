<?php

namespace App\Domains\Config\Public\Events\DTO;

use App\Domains\Config\Public\Contracts\FeatureToggle;

class FeatureToggleSnapshot
{
    public function __construct(
        public readonly string $name,
        public readonly string $domain,
        public readonly string $access,
        public readonly string $admin_visibility,
        public readonly array $roles,
    ) {}

    public static function fromFeatureToggle(FeatureToggle $featureToggle): self
    {
        return new self(
            name: $featureToggle->name,
            domain: $featureToggle->domain,
            access: $featureToggle->access->value,
            admin_visibility: $featureToggle->admin_visibility->value,
            roles: $featureToggle->roles,
        );
    }

    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'domain' => $this->domain,
            'access' => $this->access,
            'admin_visibility' => $this->admin_visibility,
            'roles' => $this->roles,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            name: (string) ($payload['name'] ?? ''),
            domain: (string) ($payload['domain'] ?? ''),
            access: (string) ($payload['access'] ?? ''),
            admin_visibility: (string) ($payload['admin_visibility'] ?? ''),
            roles: (array) ($payload['roles'] ?? []),
        );
    }
}
