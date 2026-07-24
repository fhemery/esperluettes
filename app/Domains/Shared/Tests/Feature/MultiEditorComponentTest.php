<?php

use Tests\TestCase;

uses(TestCase::class);

describe('<x-shared::multi-editor>', function () {
    it('renders simple pane by default with the content field', function () {
        $html = $this->blade(
            '<x-shared::multi-editor name="blocks" content-name="content" scope="news" />'
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
            '<x-shared::multi-editor name="blocks" scope="news" :blocks="$blocks" />',
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
        $this->blade('<x-shared::multi-editor name="blocks" scope="news" />')
            ->assertSee("appendBlock('text')", false)
            ->assertSee("appendBlock('image')", false);
    });

    it('offers both block types in the insert affordance popover', function () {
        // The insert "+" lets the author choose the type to insert at that spot.
        $this->blade(
            '<x-shared::multi-editor name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [['type' => 'text', 'html' => '<p>x</p>']]]
        )
            ->assertSee("insertAfter(\$el, 'text')", false)
            ->assertSee("insertAfter(\$el, 'image')", false);
    });
});
