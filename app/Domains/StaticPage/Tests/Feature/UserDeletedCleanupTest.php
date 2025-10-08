<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StaticPage\Private\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('keeps static page accessible and clears creator after user deletion', function () {
    // Arrange: create an admin who authors a published page
    $admin = admin($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);
    $page = StaticPage::factory()->published()->create([
        'title' => 'About',
        'slug' => 'about',
        'created_by' => $admin->id,
    ]);

    // Sanity: page is accessible before deletion
    $this->get('/' . $page->slug)->assertOk()->assertSee('About');

    // Act: delete the admin
    deleteUser($this, $admin);

    // Assert: page still accessible to guests
    $this->get('/' . $page->slug)->assertOk()->assertSee('About');

    // DB: creator id is null
    $this->assertDatabaseHas('static_pages', [
        'id' => $page->id,
        'created_by' => null,
    ]);
});
