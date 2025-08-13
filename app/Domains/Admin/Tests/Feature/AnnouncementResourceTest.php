<?php

use App\Domains\Admin\Filament\Resources\Announcement\AnnouncementResource\Pages\CreateAnnouncement;
use App\Domains\Admin\Filament\Resources\Announcement\AnnouncementResource\Pages\EditAnnouncement;
use App\Domains\Announcement\Models\Announcement;
use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAdminForCrud(): User {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');
    return $admin;
}

it('creates a pinned published announcement and auto-assigns display order via create page', function () {
    $admin = makeAdminForCrud();
    $this->actingAs($admin);

    $title = 'Created via Resource';

    Livewire::test(CreateAnnouncement::class)
        ->fillForm([
            'title' => $title,
            'slug' => 'created-via-resource',
            'summary' => 'Summary',
            'content' => '<p>Body</p>',
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now(),
            'is_pinned' => true,
            'display_order' => null,
            'meta_description' => 'Meta',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $a = Announcement::query()->where('slug', 'created-via-resource')->first();

    expect($a)->not->toBeNull();
    expect($a->status)->toBe('published');
    expect((bool) $a->is_pinned)->toBeTrue();
    expect($a->display_order)->not->toBeNull();
});

it('unpinning via edit clears display order via edit page', function () {
    $admin = makeAdminForCrud();
    $this->actingAs($admin);

    $a = Announcement::factory()->published()->pinned()->create([
        'title' => 'Pinned initially',
        'slug' => 'pinned-initially',
        'display_order' => 5,
    ]);

    Livewire::test(EditAnnouncement::class, ['record' => $a->getKey()])
        ->fillForm([
            'title' => $a->title,
            'slug' => $a->slug,
            'summary' => $a->summary,
            'content' => $a->content,
            'status' => $a->status,
            'published_at' => $a->published_at,
            'meta_description' => $a->meta_description,
            'is_pinned' => false,
            'display_order' => $a->display_order,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $a->refresh();
    expect((bool) $a->is_pinned)->toBeFalse();
    expect($a->display_order)->toBeNull();
});
