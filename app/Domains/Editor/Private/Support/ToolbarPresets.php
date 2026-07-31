<?php

declare(strict_types=1);

namespace App\Domains\Editor\Private\Support;

/**
 * The named toolbar token lists the authoring components accept.
 *
 * Presets are named after the capability they add, never after the domain that
 * happens to use them — Editor must not encode who its consumers are.
 */
final class ToolbarPresets
{
    private const DEFAULT = ['bold', 'italic', 'underline', 'strike', 'blockquote', 'align', 'list', 'custom-emoji'];

    /**
     * @return array<string, list<string>>
     */
    private static function all(): array
    {
        return [
            'default' => self::DEFAULT,
            'links' => [...self::DEFAULT, 'link'],
            // `header` sits after `strike`, where every call site's literal had it.
            'editorial' => ['bold', 'italic', 'underline', 'strike', 'header', 'blockquote', 'align', 'list', 'custom-emoji', 'link'],
            'narrative' => [...self::DEFAULT, 'link', 'spoiler'],
        ];
    }

    /**
     * Resolve a component's `toolbar` prop.
     *
     * An array is a caller-supplied token list and is returned as-is, bypassing
     * presets. A string names a preset; an unknown name falls back to `default`.
     *
     * @param  array<int|string, string>|string  $toolbar
     * @return list<string>
     */
    public static function resolve(array|string $toolbar): array
    {
        if (is_array($toolbar)) {
            return array_values($toolbar);
        }

        return self::all()[$toolbar] ?? self::all()['default'];
    }
}
