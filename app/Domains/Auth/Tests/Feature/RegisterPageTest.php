<?php

use App\Domains\Auth\Public\Support\AuthConfigKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Register Page - Activation Code Field Display', function () {

    describe('when require_activation_code is true', function () {

        beforeEach(function () {
            setActivationCodeRequired(true);
        });

        it('shows activation code field', function () {
            $response = $this->get(route('register'));

            $response->assertOk();
            $response->assertSee('activation_code');
        });

        it('shows field as mandatory (no optionnel label)', function () {
            $response = $this->get(route('register'));

            $response->assertOk();
            $response->assertDontSee(__('auth::register.form.activation_code.optional'));
        });

        it('shows mandatory tooltip with correct message', function () {
            $response = $this->get(route('register'));

            $response->assertOk();
            $response->assertSee(__('auth::register.form.activation_code.tooltip_mandatory'), false);
        });

        it('shows Plus d\'infos link to /faq/statuts in tooltip', function () {
            $response = $this->get(route('register'));

            $response->assertOk();
            $response->assertSee('/faq/statuts');
            $response->assertSee(__('auth::register.form.activation_code.more_info'), false);
        });

    });

    describe('when require_activation_code is false', function () {

        beforeEach(function () {
            setActivationCodeRequired(false);
        });

        it('shows activation code field', function () {
            $response = $this->get(route('register'));

            $response->assertOk();
            $response->assertSee('activation_code');
        });

        it('shows field as optional with (optionnel) in label', function () {
            $response = $this->get(route('register'));

            $response->assertOk();
            $response->assertSee(__('auth::register.form.activation_code.optional'));
        });

        it('shows optional tooltip with sponsorship explanation', function () {
            $response = $this->get(route('register'));

            $response->assertOk();
            $response->assertSee(__('auth::register.form.activation_code.tooltip_optional'), false);
        });

        it('shows Plus d\'infos link to /faq/statuts in tooltip', function () {
            $response = $this->get(route('register'));

            $response->assertOk();
            $response->assertSee('/faq/statuts');
            $response->assertSee(__('auth::register.form.activation_code.more_info'), false);
        });

    });

    describe('optional activation code validation', function () {

        beforeEach(function () {
            setActivationCodeRequired(false);
        });

        it('accepts registration without activation code when optional', function () {
            $response = $this->post('/register', [
                'name' => 'No Code User',
                'email' => 'nocode@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('users', ['email' => 'nocode@example.com']);
        });

        it('rejects registration with invalid activation code when optional', function () {
            $response = $this->from('/register')->post('/register', [
                'name' => 'Invalid Code User',
                'email' => 'invalidcode@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
                'activation_code' => 'INVALID-CODE-HERE',
            ]);

            $response->assertRedirect('/register');
            $response->assertSessionHasErrors(['activation_code']);
            $this->assertDatabaseMissing('users', ['email' => 'invalidcode@example.com']);
        });

        it('rejects registration with expired activation code when optional', function () {
            // Create an expired activation code
            $code = createActivationCode(expiresAt: now()->subDay());

            $response = $this->from('/register')->post('/register', [
                'name' => 'Expired Code User',
                'email' => 'expiredcode@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
                'activation_code' => $code->code,
            ]);

            $response->assertRedirect('/register');
            $response->assertSessionHasErrors(['activation_code']);
            $this->assertDatabaseMissing('users', ['email' => 'expiredcode@example.com']);
        });

        it('rejects registration with already used activation code when optional', function () {
            // Create a used activation code
            $existingUser = registerUserThroughForm($this, ['email' => 'existing@example.com']);
            $code = createActivationCode(usedByUserId: $existingUser->id);

            $this->post('/logout');

            $response = $this->from('/register')->post('/register', [
                'name' => 'Used Code User',
                'email' => 'usedcode@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
                'activation_code' => $code->code,
            ]);

            $response->assertRedirect('/register');
            $response->assertSessionHasErrors(['activation_code']);
            $this->assertDatabaseMissing('users', ['email' => 'usedcode@example.com']);
        });

        it('accepts registration with valid activation code when optional', function () {
            $code = createActivationCode();

            $response = $this->post('/register', [
                'name' => 'Valid Code User',
                'email' => 'validcode@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
                'activation_code' => $code->code,
            ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('users', ['email' => 'validcode@example.com']);
        });

    });

});
