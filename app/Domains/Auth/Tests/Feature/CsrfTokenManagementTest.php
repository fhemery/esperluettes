<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CsrfTokenManagementTest', function () {

    describe('when not logged', function() {
        it('should return an error', function () {
            Auth::logout();
            $response = $this->getJson('/auth/csrf-token');
            $response->assertStatus(401);
        });
    });

    describe('when logged', function() {
        it('should return the current CSRF token', function () {
            $this->actingAs(alice($this));

            $response = $this->getJson('/auth/csrf-token');
            $response->assertStatus(200);
            $response->assertExactJson(['token' => csrf_token()]);

            
        });

        it('should not regenerate the token', function() {
            $this->actingAs(alice($this));
            $token = csrf_token();
            
            $response = $this->getJson('/auth/csrf-token');
            $response->assertStatus(200);
            $response->assertExactJson(['token' => $token]); 

            // Call a second time
            $response = $this->getJson('/auth/csrf-token');
            $response->assertStatus(200);
            $response->assertExactJson(['token' => $token]); 
        });

        it('should return a new token after session regeneration', function() {
            $this->actingAs(alice($this));
            $token = csrf_token();
            
            $response = $this->getJson('/auth/csrf-token');
            $response->assertStatus(200);
            $response->assertExactJson(['token' => $token]); 

            // Simulate session regeneration (happens on login/logout)
            session()->regenerate();
            $this->actingAs(alice($this));

            // Token should be different after session regeneration
            $response = $this->getJson('/auth/csrf-token');
            $response->assertStatus(200);
            $newToken = $response->json('token');
            
            expect($newToken)->not->toBe($token);
        });
    });
});