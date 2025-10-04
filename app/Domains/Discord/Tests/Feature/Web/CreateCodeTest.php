<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('generate connection code (web)', function () {
    it('returns 401 for guests', function () {
        $resp = $this->postJson('/discord/connect/code');
        $resp->assertStatus(401);
    });

    it('returns 200 and a JSON code for authenticated users', function () {
        $user = alice($this);
        $this->actingAs($user);

        $resp = $this->postJson('/discord/connect/code');
        $resp->assertOk()
            ->assertJsonStructure(['code']);

        $code = $resp->json('code');
        $this->assertIsString($code);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $code);
    });
});
