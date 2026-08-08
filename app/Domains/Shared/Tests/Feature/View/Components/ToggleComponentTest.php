<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

describe('<x-shared::toggle>', function () {
    it('renders a named checkbox without sr-only', function () {
        $html = (string) $this->blade('<x-shared::toggle name="is_pinned" />');

        expect($html)->toContain('name="is_pinned"');
        expect($html)->toContain('type="checkbox"');
        expect($html)->not->toContain('sr-only');
    });

    it('positions the checkbox over the track for focus geometry', function () {
        $html = (string) $this->blade('<x-shared::toggle name="is_pinned" />');

        // Relative positioning on the label or an equivalent track wrapper.
        expect($html)->toMatch('/<label[^>]*\brelative\b|<span[^>]*\brelative\b[^>]*>[\s\S]*type="checkbox"/');
        expect($html)->toMatch('/type="checkbox"[^>]*\babsolute\b|\babsolute\b[^>]*type="checkbox"/');
        expect($html)->toMatch('/\b(inset-0|w-11|h-6|w-full|h-full)\b/');
    });

    it('keeps peer styling hooks', function () {
        $html = (string) $this->blade('<x-shared::toggle name="is_pinned" />');

        expect($html)->toMatch('/type="checkbox"[^>]*\bpeer\b|\bpeer\b[^>]*type="checkbox"/');
        expect($html)->toContain('peer-checked:');
        expect($html)->toContain('peer-focus:');
    });

    it('honours checked, disabled, value, id, and label props', function () {
        $html = (string) $this->blade(
            '<x-shared::toggle name="is_pinned" id="pin-toggle" value="yes" label="Pin me" :checked="true" :disabled="true" />'
        );

        expect($html)->toContain('id="pin-toggle"');
        expect($html)->toContain('value="yes"');
        expect($html)->toContain('checked');
        expect($html)->toContain('disabled');
        expect($html)->toContain('Pin me');
    });
});
