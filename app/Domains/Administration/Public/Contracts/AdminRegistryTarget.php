<?php

declare(strict_types=1);

namespace App\Domains\Administration\Public\Contracts;

use Illuminate\Support\Facades\Route;

final class AdminRegistryTarget
{
    private function __construct(
        private readonly string $type,
        private readonly string $value,
        private readonly array $parameters = []
    ) {}

    /**
     * Create a target with a hardcoded URL
     */
    public static function url(string $url): self
    {
        return new self('url', $url);
    }

    /**
     * Create a target with a route name
     */
    public static function route(string $routeName, array $parameters = []): self
    {
        return new self('route', $routeName, $parameters);
    }

    /**
     * Get the resolved URL for this target
     */
    public function getTargetUrl(): string
    {
        return match ($this->type) {
            'url' => $this->value,
            'route' => route($this->value, $this->parameters),
            default => throw new \InvalidArgumentException("Unknown target type: {$this->type}")
        };
    }

    /**
     * Check if this target is a route
     */
    public function isRoute(): bool
    {
        return $this->type === 'route';
    }

    /**
     * Check if this target is a URL
     */
    public function isUrl(): bool
    {
        return $this->type === 'url';
    }

    /**
     * Get the raw value (route name or URL)
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get route parameters (only for route targets)
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
