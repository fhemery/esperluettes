<?php

namespace App\Domains\Config\Private\Repositories;

use App\Domains\Config\Private\Models\ConfigParameterValue;
use Illuminate\Support\Collection;

class ConfigParameterRepository
{
    /**
     * Get all stored parameter values.
     *
     * @return Collection<int, ConfigParameterValue>
     */
    public function all(): Collection
    {
        return ConfigParameterValue::all();
    }

    /**
     * Find a parameter value by domain and key.
     */
    public function findByDomainAndKey(string $domain, string $key): ?ConfigParameterValue
    {
        return ConfigParameterValue::where('domain', $domain)
            ->where('key', $key)
            ->first();
    }

    /**
     * Create or update a parameter value.
     *
     * @param array{domain: string, key: string, value: string, updated_by: ?int} $data
     */
    public function upsert(array $data): ConfigParameterValue
    {
        return ConfigParameterValue::updateOrCreate(
            ['domain' => $data['domain'], 'key' => $data['key']],
            ['value' => $data['value'], 'updated_by' => $data['updated_by']]
        );
    }

    /**
     * Delete a parameter value (reset to default).
     */
    public function delete(ConfigParameterValue $model): void
    {
        $model->delete();
    }

    /**
     * Delete a parameter value by domain and key.
     */
    public function deleteByDomainAndKey(string $domain, string $key): bool
    {
        return ConfigParameterValue::where('domain', $domain)
            ->where('key', $key)
            ->delete() > 0;
    }
}
