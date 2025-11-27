<?php

use App\Domains\Auth\Private\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ComplianceController', function () {

    beforeEach(function () {
        Storage::fake('private');
    });

    describe('Parental Authorization Upload', function () {

        beforeEach(function () {
            $this->user = alice($this, [
                'is_under_15' => true,
                'parental_authorization_verified_at' => null,
            ]);
        });

        it('rejects upload when no file is provided', function () {
            $response = $this->actingAs($this->user)
                ->post(route('compliance.parental.upload'), []);

            $response->assertRedirect();
            $response->assertSessionHasErrors('parental_authorization');
            
            $this->expect(session('errors')->get('parental_authorization'))
                ->toContain(__('auth::compliance.parental_authorization.required'));
        });

        it('rejects upload when non-file value is provided', function () {
            $response = $this->actingAs($this->user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => 'not-a-file',
                ]);

            $response->assertRedirect();
            $response->assertSessionHasErrors('parental_authorization');
            
            $this->expect(session('errors')->get('parental_authorization'))
                ->toContain(__('auth::compliance.parental_authorization.file'));
        });

        it('rejects upload when file is not PDF', function () {
            $file = UploadedFile::fake()->create('invalid.jpg', 1000);

            $response = $this->actingAs($this->user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ]);

            $response->assertRedirect();
            $response->assertSessionHasErrors('parental_authorization');
            
            $this->expect(session('errors')->get('parental_authorization'))
                ->toContain(__('auth::compliance.parental_authorization.mimes'));
        });

        it('rejects upload when file is too large (>5MB)', function () {
            $file = UploadedFile::fake()->create('large.pdf', 6000); // 6MB

            $response = $this->actingAs($this->user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ]);

            $response->assertRedirect();
            $response->assertSessionHasErrors('parental_authorization');
            
            $this->expect(session('errors')->get('parental_authorization'))
                ->toContain(__('auth::compliance.parental_authorization.max'));
        });

        it('rejects upload when file is exactly 5MB but invalid format', function () {
            $file = UploadedFile::fake()->create('invalid.txt', 5120); // 5MB

            $response = $this->actingAs($this->user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ]);

            $response->assertRedirect();
            $response->assertSessionHasErrors('parental_authorization');
            
            $this->expect(session('errors')->get('parental_authorization'))
                ->toContain(__('auth::compliance.parental_authorization.mimes'));
        });

        it('accepts upload when valid PDF file is provided', function () {
            $file = UploadedFile::fake()->create('parental_auth.pdf', 1024); // 1KB

            $response = $this->actingAs($this->user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ]);

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHasNoErrors();
            
            // Verify user is marked as verified
            $this->expect($this->user->fresh()->parental_authorization_verified_at)
                ->not->toBeNull();
            
            // Verify file was stored
            Storage::disk('private')->assertExists('parental_authorizations/' . $file->hashName());
        });

        it('accepts upload when PDF file is exactly 5MB', function () {
            $file = UploadedFile::fake()->create('parental_auth.pdf', 5120); // 5MB

            $response = $this->actingAs($this->user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ]);

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHasNoErrors();
            
            // Verify user is marked as verified
            $this->expect($this->user->fresh()->parental_authorization_verified_at)
                ->not->toBeNull();
        });

        it('handles empty file upload gracefully', function () {
            $file = UploadedFile::fake()->create('empty.pdf', 0);

            $response = $this->actingAs($this->user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ]);

            $response->assertRedirect();
            $response->assertSessionHasErrors('parental_authorization');
            
            $this->expect(session('errors')->get('parental_authorization'))
                ->toContain(__('auth::compliance.parental_authorization.min'));
        });

        it('redirects adult users to dashboard', function () {
            $adultUser = bob($this);
            
            $file = UploadedFile::fake()->create('parental_auth.pdf', 1024);

            $response = $this->actingAs($adultUser)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ]);

            $response->assertRedirect(route('dashboard'));
            
            // Verify user status unchanged
            $this->expect($adultUser->fresh()->parental_authorization_verified_at)
                ->toBeNull();
        });

        it('redirects already verified underage users to dashboard', function () {
            $verifiedUser = alice($this, [
                'is_under_15' => true,
                'parental_authorization_verified_at' => now(),
            ]);
            
            $file = UploadedFile::fake()->create('parental_auth.pdf', 1024);

            $response = $this->actingAs($verifiedUser)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ]);

            $response->assertRedirect(route('dashboard'));
        });

        it('requires authentication', function () {
            Auth::logout();
            
            $file = UploadedFile::fake()->create('parental_auth.pdf', 1024);

            $response = $this->post(route('compliance.parental.upload'), [
                'parental_authorization' => $file,
            ]);

            $response->assertRedirect(route('login'));
        });
    });
});
