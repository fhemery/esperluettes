<?php

declare(strict_types=1);

namespace App\Domains\Editor\Public\Blocks;

/**
 * Registered MultiEdit block types, keyed by EditorBlockType::key(), in
 * registration order. Built-ins (`text`, `image`) are registered first by
 * EditorServiceProvider.
 */
final class EditorBlockRegistry
{
    /** @var array<string, EditorBlockType> */
    private array $types = [];

    public function register(EditorBlockType $type): void
    {
        $key = $type->key();
        if (isset($this->types[$key])) {
            throw new \InvalidArgumentException("Editor block type '{$key}' is already registered.");
        }
        $this->types[$key] = $type;
    }

    public function get(string $key): ?EditorBlockType
    {
        return $this->types[$key] ?? null;
    }

    /**
     * @return list<EditorBlockType>
     */
    public function all(): array
    {
        return array_values($this->types);
    }
}
