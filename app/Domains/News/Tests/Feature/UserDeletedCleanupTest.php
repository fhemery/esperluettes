<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('keeps news accessible and clears creator after user deletion', function () {
    // Arrange: create an admin who authors a published news item
    $admin = admin($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);
    $news = News::factory()->create([
        'title' => 'Breaking',
        'slug' => 'breaking',
        'summary' => 'Summary',
        'content' => '<p>Body</p>',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $admin->id,
    ]);

    // Sanity: news page is accessible before deletion
    $this->get(route('news.show', ['slug' => $news->slug]))->assertOk()->assertSee('Breaking');

    // Act: delete the admin
    deleteUser($this, $admin);

    // Assert: news still accessible to guests
    $this->get(route('news.show', ['slug' => $news->slug]))->assertOk()->assertSee('Breaking');

    // DB: creator id is null
    $this->assertDatabaseHas('news', [
        'id' => $news->id,
        'created_by' => null,
    ]);
});
