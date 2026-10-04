<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

describe('<x-media::sound-field>', function () {
    it('renders the file input and the remove flag under the given name', function () {
        $html = $this->withViewErrors([])->blade('<x-media::sound-field name="gift_sound" />');

        $html->assertSee('name="gift_sound"', false);
        $html->assertSee('name="gift_sound_remove"', false);
    });

    it('uses the consumer supplied preview url as the current sound', function () {
        $this->withViewErrors([])
            ->blade('<x-media::sound-field name="gift_sound" preview-url="/x/sound/1" />')
            ->assertSee('mediaSoundField(', false)
            ->assertSee('\/x\/sound\/1', false);
    });

    it('renders the empty drop zone copy when there is no preview url', function () {
        $this->withViewErrors([])
            ->blade('<x-media::sound-field name="gift_sound" />')
            ->assertSee(__('media::sound-field.drop_or_click'))
            ->assertSee(__('media::sound-field.max_size', ['size' => 10]));
    });

    it('hides the delete control when removable is false', function () {
        $this->withViewErrors([])
            ->blade('<x-media::sound-field name="gift_sound" :removable="false" />')
            ->assertDontSee(__('media::sound-field.delete'))
            ->assertDontSee('markForDeletion()', false);
    });

    it('never builds a storage url', function () {
        $this->withViewErrors([])
            ->blade('<x-media::sound-field name="gift_sound" preview-url="/x/sound/1" />')
            ->assertDontSee('/storage/', false)
            ->assertDontSee('\/storage\/', false);
    });
});
