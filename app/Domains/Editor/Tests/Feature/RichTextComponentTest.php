<?php

use Tests\TestCase;

uses(TestCase::class);

describe('<x-editor::rich-text>', function () {
    it('renders the hidden textarea, the counter and the default toolbar', function () {
        $html = $this->blade(
            '<x-editor::rich-text name="body" id="e1" :min="10" :max="100" />'
        );

        // Hidden field the form actually submits.
        $html->assertSee('name="body"', false);
        $html->assertSee('id="quill-editor-area-e1"', false);

        // Counter block, keyed by the editor id.
        $html->assertSee('id="quill-counter-e1"', false);
        $html->assertSee('id="quill-unit-e1"', false);
        $html->assertSee('/ 100', false);

        // Default toolbar token list, unchanged by the move.
        $html->assertSee(
            'data-toolbar="' . e(json_encode(
                ['bold', 'italic', 'underline', 'strike', 'blockquote', 'align', 'list', 'custom-emoji'],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )) . '"',
            false
        );
    });

    it('takes its strings from the editor:: translation namespace', function () {
        // The test locale is `zz`, so translations render as raw keys — asserting
        // on `__()` here proves which key the component asks for, not its value.
        $html = $this->blade(
            '<x-editor::rich-text name="body" id="e1" :min="10" :max="100" />'
        );

        $html->assertSee(trans_choice('editor::rich-text.character', 2), false);
        $html->assertSee(trans_choice('editor::rich-text.min-characters', 10, ['min' => 10]), false);
        $html->assertSee(__('editor::rich-text.clean'), false);
    });

    it('registers the editor:: namespace so those keys resolve in French', function () {
        // The value check the `zz` locale cannot make: the provider's
        // loadTranslationsFrom really points at Editor's own lang directory.
        app()->setLocale('fr');

        expect(__('editor::rich-text.clean'))->toBe('Effacer la mise en forme');
        expect(trans_choice('editor::rich-text.character', 2))->toBe('caractères');
    });
});
