<?php

use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('renders root comments HTML for the first page', function () {
    $entityType = 'chapter';
    $entityId = 123;

    $user = alice($this, roles: [Roles::USER]);
    $this->actingAs($user);

    // Create 3 root comments: Hello 0, Hello 1, Hello 2
    createSeveralComments(3, $entityType, $entityId, 'Hello');

    // PerPage = 2 to force pagination
    $response = $this->get(route('comments.fragments', [
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'page' => 1,
        'per_page' => 2,
    ]));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    // Only the 2 most recent should be present (created_at desc): Hello 2 and Hello 1
    $response->assertSee('Hello 2', false);
    $response->assertSee('Hello 1', false);
    $response->assertDontSee('Hello 0', false);
    // Next page header should be set
    $response->assertHeader('X-Next-Page', '2');
});

it('renders child comments HTML for the first page', function () {
    $entityType = 'chapter';
    $entityId = 123;

    $user = alice($this, roles: [Roles::USER]);
    $this->actingAs($user);

    // Create 3 root comments: Hello 0, Hello 1, Hello 2
    $commentId = createComment($entityType, $entityId, 'Hello');
    $childCommentId = createComment($entityType, $entityId, 'Hello from child', $commentId);

    $response = $this->get(route('comments.fragments', [
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'page' => 1,
        'per_page' => 2,
    ]));

    $response->assertStatus(200);
    $response->assertSee('Hello from child', false);
});

it('returns 401 for guests (no role) when listing fragments', function () {
    $entityType = 'chapter';
    $entityId = 1;

    $response = $this->get(route('comments.fragments', [
        'entity_type' => $entityType,
        'entity_id' => $entityId,
    ]));

    $response->assertStatus(401);
});
