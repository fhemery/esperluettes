<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('renders comments as unknown user after the author account is deleted', function () {
    // Arrange
    $entityType = 'default';
    $entityId = 123;

    // Author creates a comment
    $author = alice($this, roles: [Roles::USER_CONFIRMED]);
    $this->actingAs($author);
    $commentId = createComment($entityType, $entityId, 'Hello from soon-to-be-deleted user');

    // Act: delete the user via the real flow (ensures domain events fire)
    deleteUser($this, $author);

    // Assert via controller: as another logged-in user, fetch fragments
    $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);
    $this->actingAs($viewer);

    $response = $this->get(route('comments.fragments', [
        'entity_type' => $entityType,
        'entity_id'   => $entityId,
        'page'        => 1,
        'per_page'    => 5,
    ]));

    $response->assertStatus(200);
    // Default avatar image should be used
    $response->assertSee('images/default-avatar.svg', false);
    // Default translated name should be displayed
    $response->assertSee(__('comment::comments.unknown_user'));
});
