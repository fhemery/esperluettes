<?php

use Tests\TestCase;

uses(TestCase::class);

describe('<x-media::image-field>', function () {
    it('renders the library picker by default', function () {
        $html = $this->blade(
            '<x-media::image-field name="header_image" scope="news" />'
        );

        $html->assertSee(__('media::image-field.choose_existing'));
        $html->assertSee(__('media::image-field.picker_title'));
        $html->assertSee('openPicker()', false);
    });

    it('hides the library picker when allowLibrary is false', function () {
        $html = $this->blade(
            '<x-media::image-field name="gift_image" scope="secret-gift/1" :allow-library="false" />'
        );

        $html->assertDontSee(__('media::image-field.choose_existing'));
        $html->assertDontSee(__('media::image-field.picker_title'));
        $html->assertDontSee('openPicker()', false);
        $html->assertSee(__('media::image-field.upload'));
        $html->assertSee('name="gift_image[file]"', false);
    });

    it('uses the consumer supplied preview url when given', function () {
        $html = $this->blade(
            '<x-media::image-field name="gift_image" scope="secret-gift/1" :path="$path" :preview-url="$url" />',
            ['path' => 'secret-gift/1/abc.jpg', 'url' => '/calendar/secret-gift/1/image/2']
        );

        $html->assertSee('\/calendar\/secret-gift\/1\/image\/2', false);
        $html->assertDontSee('storage', false);
    });

    it('falls back to the media variant url when no preview url is given', function () {
        $this->blade(
            '<x-media::image-field name="header_image" scope="news" path="news/a.jpg" />'
        )->assertSee('news\/a-400w.webp', false);
    });

    it('still emits name[path] and name[file] when the library is hidden', function () {
        $html = $this->blade(
            '<x-media::image-field name="gift_image" scope="secret-gift/1" :allow-library="false" />'
        );

        $html->assertSee('name="gift_image[path]"', false);
        $html->assertSee('name="gift_image[file]"', false);
    });

    it('does not call media url helpers for a private path when a preview url is set', function () {
        $api = Mockery::mock(\App\Domains\Media\Public\Api\MediaPublicApi::class);
        $api->shouldNotReceive('variantUrl');
        $api->shouldNotReceive('originalUrl');
        app()->instance(\App\Domains\Media\Public\Api\MediaPublicApi::class, $api);

        $this->blade(
            '<x-media::image-field name="gift_image" scope="secret-gift/1" :path="$path" :preview-url="$url" :allow-library="false" />',
            ['path' => 'secret-gift/1/abc.jpg', 'url' => '/calendar/secret-gift/1/image/2']
        )->assertSee('name="gift_image[file]"', false);
    });
});
