<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

describe('Shared segmented control', function () {
    it('renders a radiogroup with its aria-label and one radio per option', function () {
        $html = (string) $this->blade(
            '<x-shared::segmented-control name="demo" label="Demo" :options="$options" selected="b" />',
            ['options' => ['a' => 'Option A', 'b' => 'Option B']]
        );

        expect($html)
            ->toContain('role="radiogroup"')
            ->toContain('aria-label="Demo"')
            ->toContain('data-segmented-control')
            ->toContain('data-name="demo"')
            ->toContain('Option A')
            ->toContain('Option B')
            ->and(substr_count($html, 'role="radio"'))->toBe(2);
    });

    it('marks the selected option checked and focusable, the others not', function () {
        $html = (string) $this->blade(
            '<x-shared::segmented-control name="demo" label="Demo" :options="$options" selected="b" />',
            ['options' => ['a' => 'A', 'b' => 'B']]
        );

        expect($html)
            ->toMatch('/<button[^>]*aria-checked="false"[^>]*tabindex="-1"[^>]*data-value="a"/s')
            ->toMatch('/<button[^>]*aria-checked="true"[^>]*tabindex="0"[^>]*data-value="b"/s')
            ->toContain('data-value="b"');
    });

    it('defaults to the first option when no selection is given', function () {
        $html = (string) $this->blade(
            '<x-shared::segmented-control name="demo" label="Demo" :options="$options" />',
            ['options' => ['a' => 'A', 'b' => 'B']]
        );

        expect($html)
            ->toMatch('/<button[^>]*aria-checked="true"[^>]*tabindex="0"[^>]*data-value="a"/s')
            ->toMatch('/<button[^>]*aria-checked="false"[^>]*tabindex="-1"[^>]*data-value="b"/s');
    });

    it('exposes name, default value and storage key to Alpine', function () {
        $html = (string) $this->blade(
            '<x-shared::segmented-control name="demo" label="Demo" :options="$options" selected="b" storage-key="demo.mode" />',
            ['options' => ['a' => 'A', 'b' => 'B']]
        );

        expect($html)
            ->toContain('x-data="segmentedControl(')
            ->toContain("name: 'demo'")
            ->toContain("selected: 'b'")
            ->toContain("storageKey: 'demo.mode'")
            ->toContain('options: JSON.parse(');
    });
});
