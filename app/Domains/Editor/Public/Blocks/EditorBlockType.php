<?php

declare(strict_types=1);

namespace App\Domains\Editor\Public\Blocks;

/**
 * One kind of MultiEdit block. `text` and `image` are built-in registrations;
 * other domains register their own in EditorBlockRegistry.
 */
interface EditorBlockType
{
    /** Stable key stored in content_blocks[*]['type']. */
    public function key(): string;

    /** Translation key of the palette / insert-menu label. */
    public function labelKey(): string;

    /** Material Symbols name of the palette / insert-menu icon. */
    public function icon(): string;

    /**
     * Blade view rendering one block in the editor. Receives $uid, $name,
     * $block (array|null for a new one) and $context (the blockContext prop).
     */
    public function editorView(): string;

    /**
     * Render one normalised block to HTML. The output is not re-sanitized by
     * Editor: the type escapes its own output. $context is the consumer render
     * context plus the reserved `profile` key (Purifier profile).
     *
     * @param array<string, mixed> $block
     * @param array<string, mixed> $context
     */
    public function render(array $block, array $context): string;
}
