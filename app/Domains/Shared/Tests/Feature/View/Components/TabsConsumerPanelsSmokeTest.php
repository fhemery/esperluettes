<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

describe('Shared tabs consumer panels', function () {
    it('stamps tabpanel ARIA on Search results panels', function () {
        // One host is enough to prove the consumer convention; other hosts
        // follow the same markup contract documented in Shared AGENTS.md.
        $story = (object) [
            'url' => '/stories/1',
            'cover_type' => 'default',
            'cover_url' => null,
            'title' => 'Example',
            'authors' => ['Author'],
        ];

        $this->blade(
            '@include("search::partials.search-results", $data)',
            [
                'data' => [
                    'storiesPage' => 1,
                    'profilesPage' => 1,
                    'perPage' => 5,
                    'stories' => [
                        'total' => 1,
                        'items' => [$story],
                    ],
                    'profiles' => [
                        'total' => 0,
                        'items' => [],
                    ],
                ],
            ]
        )
            ->assertSee('role="tabpanel"', false)
            ->assertSee('id="tabs-panel-stories"', false)
            ->assertSee('aria-labelledby="tabs-tab-stories"', false)
            ->assertSee('id="tabs-panel-profiles"', false)
            ->assertSee('aria-labelledby="tabs-tab-profiles"', false)
            ->assertSee('id="tabs-tab-stories"', false)
            ->assertSee('aria-controls="tabs-panel-stories"', false);
    });
});
