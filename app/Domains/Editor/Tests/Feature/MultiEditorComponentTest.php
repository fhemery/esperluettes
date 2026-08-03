<?php

use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

describe('<x-editor::multi>', function () {
    it('renders simple pane by default with the content field', function () {
        $html = $this->blade(
            '<x-editor::multi name="blocks" content-name="content" scope="news" />'
        );

        $html->assertSee('name="mode"', false);
        $html->assertSee('name="blocks_order"', false);
        // Simple editor hidden textarea for the content field.
        $html->assertSee('name="content"', false);
        // Mode toggle wired.
        $html->assertSee('goSimple()', false);
        $html->assertSee('goAdvanced()', false);
    });

    it('renders initial advanced blocks and serializes by uid', function () {
        $html = $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [
                ['type' => 'text', 'html' => '<p>Intro</p>'],
                ['type' => 'image', 'path' => 'news/a.jpg', 'alt' => 'A'],
            ]]
        );

        // Text block: type + html textarea for uid b0.
        $html->assertSee('name="blocks[b0][type]"', false);
        $html->assertSee('name="blocks[b0][html]"', false);
        // Image block: type + media image-field path/file for uid b1.
        $html->assertSee('name="blocks[b1][type]"', false);
        $html->assertSee('name="blocks[b1][path]"', false);
        $html->assertSee('name="blocks[b1][file]"', false);
    });

    it('exposes text and image palette buttons', function () {
        $this->blade('<x-editor::multi name="blocks" scope="news" />')
            ->assertSee("appendBlock('text')", false)
            ->assertSee("appendBlock('image')", false);
    });

    it('offers both block types in the insert affordance popover', function () {
        // The insert "+" lets the author choose the type to insert at that spot.
        $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [['type' => 'text', 'html' => '<p>x</p>']]]
        )
            ->assertSee("insertAfter(\$el, 'text')", false)
            ->assertSee("insertAfter(\$el, 'image')", false);
    });

    it('defaults text blocks to five lines and no indent', function () {
        // Regression guard: consumers that pass neither prop (News) must keep
        // rendering exactly what they rendered before the props existed.
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [['type' => 'text', 'html' => '<p>Intro</p>']]]
        );

        expect($html)->toContain('data-nb-lines="5"')
            ->and($html)->not->toContain('data-nb-lines="15"')
            ->and($html)->not->toContain('ql-indent')
            ->and(substr_count($html, 'class="surface-read text-on-surface w-full"'))->toBe(2);
    });

    it('applies nbLines to every text block', function () {
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" :nbLines="15" />',
            ['blocks' => [
                ['type' => 'text', 'html' => '<p>One</p>'],
                ['type' => 'text', 'html' => '<p>Two</p>'],
            ]]
        );

        // Two server-rendered blocks, the <template> new blocks are cloned from,
        // and the simple pane — the same writing surface in the same form.
        expect(substr_count($html, 'data-nb-lines="15"'))->toBe(4)
            ->and($html)->not->toContain('data-nb-lines="5"');
    });

    it('applies indentParagraphs to every text block', function () {
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" :indentParagraphs="true" />',
            ['blocks' => [
                ['type' => 'text', 'html' => '<p>One</p>'],
                ['type' => 'text', 'html' => '<p>Two</p>'],
            ]]
        );

        // Same breakdown as above; `ql-indent` is the class <x-editor::rich-text>
        // emits for this prop.
        expect(substr_count($html, 'ql-indent'))->toBe(4);
    });

    it('gives dynamically added text blocks the same writing surface', function () {
        // New blocks are cloned from the Blade <template x-ref="tplText">, so the
        // props only have to reach that template for JS-inserted blocks to match.
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :nbLines="15" :indentParagraphs="true" />'
        );

        $tpl = Str::between($html, '<template x-ref="tplText">', '</template>');

        expect($tpl)->toContain('data-nb-lines="15"')
            ->and($tpl)->toContain('ql-indent');
    });

    it('forwards needsPropertyConfirm to image blocks and the image template', function () {
        $with = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="chapters/1" :blocks="$blocks" :needs-property-confirm="true" />',
            ['blocks' => [['type' => 'image', 'path' => 'chapters/1/a.jpg', 'alt' => '']]]
        );
        $without = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [['type' => 'image', 'path' => 'news/a.jpg', 'alt' => '']]]
        );

        $confirm = __('media::image-field.property_confirm');
        expect($with)->toContain($confirm)
            ->and(Str::between($with, '<template x-ref="tplImage">', '</template>'))->toContain($confirm)
            ->and($without)->not->toContain($confirm);
    });
});
