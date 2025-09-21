<?php

use App\Domains\Admin\Filament\Resources\Auth\ActivationCodeResource;
use App\Domains\Admin\Filament\Resources\Auth\ActivationCodeResource\Pages\CreateActivationCode;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Private\Services\ActivationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows sponsor and used-by with Profile display names on index page', function () {
    // Create two regular users
    $sponsor = alice($this);
    $usedBy = bob($this);
    $admin = admin($this);

    // Create code with sponsor and then mark as used by another user
    $service = app(ActivationCodeService::class);
    $code = $service->generateCode($sponsor, comment: 'test');
    $code->markAsUsed($usedBy);

    // Visit the resource index page
    $response = $this->actingAs($admin)->get(ActivationCodeResource::getUrl('index'));
    if ($response->isRedirect()) {
        $response = $this->followRedirects($response);
    }

    $response->assertOk();

    $response->assertSee('Alice');
    $response->assertSee('Bob');
});

it('creates an activation code via create page and lists it on index', function () {
    // Arrange
    $sponsor = alice($this);
    // @var User $admin
    $admin = admin($this);
    $this->actingAs($admin);

    // Act: create via Filament page (service generates random code)
    Livewire::test(CreateActivationCode::class)
        ->fillForm([
            'sponsor_user_id' => $sponsor->id,
            'comment' => 'Via UI',
            'expires_at' => null,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $response = $this->actingAs($admin)->get(ActivationCodeResource::getUrl('index'));
    if ($response->isRedirect()) {
        $response = $this->followRedirects($response);
    }
    $response->assertOk();
    $response->assertSee('Alice');
});
