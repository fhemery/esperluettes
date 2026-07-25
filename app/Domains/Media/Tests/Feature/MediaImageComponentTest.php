<?php

use Tests\TestCase;

uses(TestCase::class);

describe('<x-media::image>', function () {
    it('renders a responsive picture with webp source and jpg fallback', function () {
        $html = $this->blade(
            '<x-media::image :path="$path" :alt="$alt" :widths="[400,800]" />',
            ['path' => 'news/sep-abc.jpg', 'alt' => 'A separator']
        );

        $html->assertSee('<picture', false);
        $html->assertSee('type="image/webp"', false);
        $html->assertSee('storage/news/sep-abc-800w.webp', false);
        $html->assertSee('storage/news/sep-abc-800w.jpg', false);
        $html->assertSee('alt="A separator"', false);
    });

    it('renders a caption when provided', function () {
        $this->blade(
            '<x-media::image path="news/a.jpg" alt="x" caption="Fig. 1" />'
        )->assertSee('Fig. 1');
    });

    it('renders nothing without a path', function () {
        $this->blade('<x-media::image :path="null" alt="x" />')
            ->assertDontSee('<picture', false);
    });
});
