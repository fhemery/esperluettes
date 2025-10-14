<?php

namespace App\Domains\Config\Private\Repositories;

use App\Domains\Config\Private\Models\FeatureToggle;

class FeatureToggleRepository
{
    public function findByDomainAndName(string $domain, string $name): ?FeatureToggle
    {
        return FeatureToggle::where('domain', $domain)->where('name', $name)->first();
    }

    public function create(array $data): FeatureToggle
    {
        return FeatureToggle::create($data);
    }

    public function update(FeatureToggle $toggle, array $data): FeatureToggle
    {
        $toggle->fill($data);
        $toggle->save();
        return $toggle;
    }

    public function delete(FeatureToggle $toggle): void
    {
        $toggle->delete();
    }

    public function exists(string $domain, string $name): bool
    {
        return FeatureToggle::where('domain', $domain)->where('name', $name)->exists();
    }

    /**
     * @return array<int,FeatureToggle>
     */
    public function all(): array
    {
        return FeatureToggle::orderBy('domain')->orderBy('name')->get()->all();
    }
}
