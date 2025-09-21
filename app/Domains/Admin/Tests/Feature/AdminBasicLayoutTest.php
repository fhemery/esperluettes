<?php

declare(strict_types=1);

use App\Domains\Auth\Private\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('should redirect the user to home page when clicking on logo', function () {
    $admin = admin($this);

    $this->actingAs($admin);

    $response = $this->get('/admin');
    $response->assertOk();

    // Prefer asserting against Filament's computed panel home URL
    $homeUrl = Filament::getCurrentPanel()?->getHomeUrl();
    expect($homeUrl)->toBe('/');
});

it('should have a link to / in header logo (HTML parsing way)', function () {
    $admin = admin($this);

    $this->actingAs($admin);

    $response = $this->get('/admin');
    $response->assertOk();

    $response->assertHasAttribute('header a', 'href', '/');
    
});