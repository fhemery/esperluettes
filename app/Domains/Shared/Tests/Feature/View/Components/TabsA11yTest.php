<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

describe('Shared tabs a11y', function () {
    it('stamps id and aria-controls on tab buttons with the default prefix', function () {
        $this->blade(
            '<x-shared::tabs :tabs="$tabs" initial="about" />',
            [
                'tabs' => [
                    ['key' => 'about', 'label' => 'About'],
                    ['key' => 'bio', 'label' => 'Bio'],
                ],
            ]
        )
            ->assertSee('id="tabs-tab-about"', false)
            ->assertSee('aria-controls="tabs-panel-about"', false)
            ->assertSee('id="tabs-tab-bio"', false)
            ->assertSee('aria-controls="tabs-panel-bio"', false);
    });

    it('uses a custom id prop as the prefix', function () {
        $this->blade(
            '<x-shared::tabs id="cover" :tabs="$tabs" initial="default" />',
            [
                'tabs' => [
                    ['key' => 'default', 'label' => 'Default'],
                    ['key' => 'themed', 'label' => 'Themed'],
                ],
            ]
        )
            ->assertSee('id="cover-tab-default"', false)
            ->assertSee('aria-controls="cover-panel-default"', false)
            ->assertSee('id="cover-tab-themed"', false)
            ->assertSee('aria-controls="cover-panel-themed"', false)
            ->assertDontSee('id="tabs-tab-default"', false);
    });
});
