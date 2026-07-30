<?php

use Tests\TestCase;

uses(TestCase::class);

/**
 * The four presets, token-for-token as architecture §4.2 defines them. Any
 * change here changes what every call site renders.
 */
function expectedToolbar(string $preset): array
{
    $default = ['bold', 'italic', 'underline', 'strike', 'blockquote', 'align', 'list', 'custom-emoji'];

    return match ($preset) {
        'links' => ['bold', 'italic', 'underline', 'strike', 'blockquote', 'align', 'list', 'custom-emoji', 'link'],
        'editorial' => ['bold', 'italic', 'underline', 'strike', 'header', 'blockquote', 'align', 'list', 'custom-emoji', 'link'],
        'narrative' => ['bold', 'italic', 'underline', 'strike', 'blockquote', 'align', 'list', 'custom-emoji', 'link', 'spoiler'],
        default => $default,
    };
}

function toolbarAttribute(array $tokens): string
{
    return 'data-toolbar="' . e(json_encode($tokens, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '"';
}

describe('toolbar presets', function () {
    it('renders each preset as its exact ordered token list', function (string $preset) {
        $html = $this->blade('<x-editor::rich-text name="x" id="x" toolbar="' . $preset . '" />');

        $html->assertSee(toolbarAttribute(expectedToolbar($preset)), false);
    })->with(['default', 'links', 'editorial', 'narrative']);

    it('falls back to the default preset for an unknown name', function () {
        $html = $this->blade('<x-editor::rich-text name="x" id="x" toolbar="nope" />');

        $html->assertSee(toolbarAttribute(expectedToolbar('default')), false);
    });

    it('lets an explicit array bypass presets entirely', function () {
        $html = $this->blade('<x-editor::rich-text name="x" id="x" :toolbar="[\'bold\']" />');

        $html->assertSee(toolbarAttribute(['bold']), false);
    });

    it('fires the link wiring for a preset that carries the link token', function () {
        $html = $this->blade('<x-editor::rich-text name="x" id="x" toolbar="links" />');

        $html->assertSee('data-link-visit=', false);
        $html->assertSee('data-link-edit=', false);
        $html->assertDontSee('data-spoiler-label=', false);
    });

    it('fires the spoiler wiring for the narrative preset', function () {
        $html = $this->blade('<x-editor::rich-text name="x" id="x" toolbar="narrative" />');

        $html->assertSee('data-spoiler-label=', false);
        $html->assertSee('data-link-visit=', false);
    });

    it('resolves presets in <x-editor::multi> too, in both panes', function () {
        $html = $this->blade(
            '<x-editor::multi scope="news" toolbar="editorial" :blocks="[[\'type\' => \'text\', \'html\' => \'<p>a</p>\']]" />'
        );

        // Simple pane (a nested rich-text) and advanced pane (a text block)
        // both carry the resolved list.
        $html->assertSee(toolbarAttribute(expectedToolbar('editorial')), false);
    });
});
