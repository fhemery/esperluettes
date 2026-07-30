<?php

use Tests\TestCase;

uses(TestCase::class);

/**
 * The editor components load their own Vite bundle; consumer pages no longer
 * hand-write an `@vite` line. These are the regression net for that deletion.
 *
 * The assets are pushed on the `scripts` stack, so each case renders a
 * `@stack('scripts')` after the component to flush it.
 */

/** Counts the `<script>` tags Vite emitted for the bundle (it also emits
 *  preload links, which are not what "loaded once" means here). */
function editorBundleScriptCount(string $html): int
{
    return preg_match_all('#<script[^>]+src="[^"]*editor-bundle[^"]*"#', $html);
}

describe('editor asset loading', function () {
    it('emits the editor bundle for <x-editor::rich-text>', function () {
        $html = $this->blade(
            '<x-editor::rich-text name="body" id="e1" />@stack(\'scripts\')'
        );

        expect(editorBundleScriptCount($html->__toString()))->toBe(1);
    });

    it('emits the editor bundle for <x-editor::multi>', function () {
        $html = $this->blade(
            '<x-editor::multi name="blocks" scope="news" />@stack(\'scripts\')'
        );

        expect(editorBundleScriptCount($html->__toString()))->toBe(1);
    });

    it('emits the editor bundle exactly once when both components render', function () {
        $html = $this->blade(
            '<x-editor::rich-text name="body" id="e1" />'
            . '<x-editor::multi name="blocks" scope="news" />'
            . '@stack(\'scripts\')'
        );

        expect(editorBundleScriptCount($html->__toString()))->toBe(1);
    });

    it('emits no editor bundle when no editor renders', function () {
        $html = $this->blade('<p>no editor here</p>@stack(\'scripts\')');

        $html->assertDontSee('editor-bundle', false);
    });
});
