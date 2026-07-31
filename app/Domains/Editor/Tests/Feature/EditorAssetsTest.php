<?php

use Tests\TestCase;

uses(TestCase::class);

/**
 * The editor components load their own Vite entries — the chrome stylesheet and
 * the Quill bundle; consumer pages no longer hand-write an `@vite` line. These
 * are the regression net for that deletion.
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

/** Counts the `<link rel="stylesheet">` tags Vite emitted for the chrome CSS
 *  entry. Same reason as above: preload links are not loads. `editor-bundle`'s
 *  own stylesheet (Quill's snow theme) is excluded — it is the JS entry's. */
function editorCssLinkCount(string $html): int
{
    return preg_match_all(
        '#<link[^>]+rel="stylesheet"[^>]+href="[^"]*/editor-(?!bundle)[^"/]*\.css"#',
        $html
    );
}

describe('editor asset loading', function () {
    it('emits both editor assets for <x-editor::rich-text>', function () {
        $html = $this->blade(
            '<x-editor::rich-text name="body" id="e1" />@stack(\'scripts\')'
        )->__toString();

        expect(editorCssLinkCount($html))->toBe(1);
        expect(editorBundleScriptCount($html))->toBe(1);
    });

    it('emits both editor assets for <x-editor::multi>', function () {
        $html = $this->blade(
            '<x-editor::multi name="blocks" scope="news" />@stack(\'scripts\')'
        )->__toString();

        expect(editorCssLinkCount($html))->toBe(1);
        expect(editorBundleScriptCount($html))->toBe(1);
    });

    it('emits each editor asset exactly once when both components render', function () {
        $html = $this->blade(
            '<x-editor::rich-text name="body" id="e1" />'
            . '<x-editor::multi name="blocks" scope="news" />'
            . '@stack(\'scripts\')'
        )->__toString();

        expect(editorCssLinkCount($html))->toBe(1);
        expect(editorBundleScriptCount($html))->toBe(1);
    });

    it('emits no editor asset when no editor renders', function () {
        $html = $this->blade('<p>no editor here</p>@stack(\'scripts\')');

        $html->assertDontSee('editor-bundle', false);
        expect(editorCssLinkCount($html->__toString()))->toBe(0);
    });
});
