<?php

declare(strict_types=1);

namespace App\Domains\Media\Public\Contracts;

/**
 * Holds the MediaUsageProvider instances registered by consuming domains.
 * Bound as a singleton so registrations made in service providers persist.
 */
final class MediaUsageRegistry
{
    /** @var array<int, MediaUsageProvider> */
    private array $providers = [];

    public function register(MediaUsageProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /** @return array<int, MediaUsageProvider> */
    public function providers(): array
    {
        return $this->providers;
    }

    /**
     * Distinct set of every path currently used across all providers.
     *
     * @return array<string, true> path => true (used as a lookup set)
     */
    public function liveSet(): array
    {
        $set = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->usedPaths() as $path) {
                if ($path !== null && $path !== '') {
                    $set[$path] = true;
                }
            }
        }
        return $set;
    }

    /**
     * How many times a given path is referenced across all providers
     * (counts every occurrence, including repeats within one owner).
     */
    public function countUsages(string $path): int
    {
        $count = 0;
        foreach ($this->providers as $provider) {
            foreach ($provider->usedPaths() as $used) {
                if ($used === $path) {
                    $count++;
                }
            }
        }
        return $count;
    }
}
