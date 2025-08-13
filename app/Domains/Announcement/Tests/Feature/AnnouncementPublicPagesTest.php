<?php

use App\Domains\Announcement\Models\Announcement;
use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAdminForPublic(): User {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');
    return $admin;
}

it('shows published announcement in index with correct link', function () {
    $admin = makeAdminForPublic();
    $a = Announcement::factory()->published()->create([
        'title' => 'Published Announcement',
        'slug' => 'published-announcement',
        'created_by' => $admin->id,
    ]);

    $response = $this->get(route('announcements.index'));
    $response->assertOk();

    $response->assertSee('Published Announcement');
    $response->assertSee('/announcements/' . $a->slug, escape: false);
});

it('does not show non-published announcement in index', function () {
    $admin = makeAdminForPublic();
    Announcement::factory()->create([
        'title' => 'Draft Announcement',
        'slug' => 'draft-announcement',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    $response = $this->get(route('announcements.index'));
    $response->assertOk();

    $response->assertDontSee('Draft Announcement');
});

it('allows direct access to published announcement', function () {
    $admin = makeAdminForPublic();
    $a = Announcement::factory()->published()->create([
        'title' => 'Published Detail',
        'slug' => 'published-detail',
        'created_by' => $admin->id,
    ]);

    $response = $this->get(route('announcements.show', $a->slug));
    $response->assertOk();
    $response->assertSee('Published Detail');
});

it('returns 404 for direct access to non-published announcement', function () {
    $admin = makeAdminForPublic();
    $a = Announcement::factory()->create([
        'title' => 'Draft Detail',
        'slug' => 'draft-detail',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    $response = $this->get(route('announcements.show', $a->slug));
    $response->assertNotFound();
});
