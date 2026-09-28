<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(fn () => clearFeatureToggleDefinitions());

describe('Feature toggles admin flash', function () {
    it('shows a success flash exactly once on the feature toggles index', function () {
        $message = 'flash-once-'.uniqid();

        $response = $this->actingAs(admin($this))
            ->withSession(['success' => $message])
            ->get(route('config.admin.feature-toggles.index'))
            ->assertOk();

        expect(substr_count($response->getContent(), $message))->toBe(1);
    });

    it('shows a validation error exactly once on the feature toggles index', function () {
        $message = 'err-once-'.uniqid();
        $errors = (new ViewErrorBag())->put('default', new MessageBag(['flash_test_only' => $message]));

        $response = $this->actingAs(admin($this))
            ->withSession(['errors' => $errors])
            ->get(route('config.admin.feature-toggles.index'))
            ->assertOk();

        expect(substr_count($response->getContent(), $message))->toBe(1);
    });
});
