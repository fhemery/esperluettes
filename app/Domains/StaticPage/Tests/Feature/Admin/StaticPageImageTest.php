<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\StaticPage\Private\Support\StaticPageMediaUsageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function staticPagePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Une page',
        'slug' => 'une-page',
        'content' => '<p>Contenu</p>',
        'status' => 'draft',
    ], $overrides);
}

describe('Static page header image via Media', function () {

    it('stores an upload flat under static-pages and persists the path', function () {
        $this->actingAs(admin($this))
            ->post(route('static.admin.store'), staticPagePayload([
                'slug' => 'page-avec-image',
                'header_image' => ['file' => UploadedFile::fake()->image('header.jpg', 800, 400)],
            ]))
            ->assertRedirect(route('static.admin.index'));

        $page = StaticPage::where('slug', 'page-avec-image')->firstOrFail();

        expect($page->header_image_path)->toStartWith('static-pages/');
        // Flat, not dated: exactly one segment after the folder.
        expect($page->header_image_path)->not->toMatch('#^static-pages/\d{4}/\d{2}/#');
        expect(substr_count($page->header_image_path, '/'))->toBe(1);
        Storage::disk('public')->assertExists($page->header_image_path);
    });

    it('reuses an existing path on update and stores no new file', function () {
        Storage::disk('public')->put('static-pages/existing.jpg', 'x');
        $page = StaticPage::factory()->create(['slug' => 'reutilisation']);
        $admin = admin($this);

        // Counted after the admin exists: creating a user writes a default avatar.
        $before = count(Storage::disk('public')->allFiles());

        $this->actingAs($admin)
            ->put(route('static.admin.update', $page), staticPagePayload([
                'slug' => 'reutilisation',
                'header_image' => ['path' => 'static-pages/existing.jpg'],
            ]))
            ->assertRedirect(route('static.admin.index'));

        $page->refresh();
        expect($page->header_image_path)->toBe('static-pages/existing.jpg');
        expect(Storage::disk('public')->allFiles())->toHaveCount($before);
    });

    it('clears the header image path on removal but leaves the file on disk', function () {
        Storage::disk('public')->put('static-pages/kept.jpg', 'x');
        $page = StaticPage::factory()->create([
            'slug' => 'suppression',
            'header_image_path' => 'static-pages/kept.jpg',
        ]);

        $this->actingAs(admin($this))
            ->put(route('static.admin.update', $page), staticPagePayload([
                'slug' => 'suppression',
                'header_image' => ['path' => ''],
            ]))
            ->assertRedirect(route('static.admin.index'));

        $page->refresh();
        expect($page->header_image_path)->toBeNull();
        Storage::disk('public')->assertExists('static-pages/kept.jpg');
    });

    it('replaces the header image with a new upload without deleting the previous file', function () {
        Storage::disk('public')->put('static-pages/previous.jpg', 'x');
        $page = StaticPage::factory()->create([
            'slug' => 'remplacement',
            'header_image_path' => 'static-pages/previous.jpg',
        ]);

        $this->actingAs(admin($this))
            ->put(route('static.admin.update', $page), staticPagePayload([
                'slug' => 'remplacement',
                'header_image' => ['file' => UploadedFile::fake()->image('new.jpg', 800, 400)],
            ]))
            ->assertRedirect(route('static.admin.index'));

        $page->refresh();
        expect($page->header_image_path)->not->toBe('static-pages/previous.jpg');
        Storage::disk('public')->assertExists('static-pages/previous.jpg');
    });

    it('leaves the header file on disk when the static page is deleted', function () {
        Storage::disk('public')->put('static-pages/orphaned-by-delete.jpg', 'x');
        $page = StaticPage::factory()->create([
            'slug' => 'a-supprimer',
            'header_image_path' => 'static-pages/orphaned-by-delete.jpg',
        ]);

        $this->actingAs(admin($this))
            ->delete(route('static.admin.destroy', $page))
            ->assertRedirect(route('static.admin.index'));

        $this->assertDatabaseMissing('static_pages', ['id' => $page->id]);
        Storage::disk('public')->assertExists('static-pages/orphaned-by-delete.jpg');
    });
});

describe('StaticPageMediaUsageProvider', function () {

    it('reports every non-null header image path, grandfathered dated ones included', function () {
        StaticPage::factory()->create(['slug' => 'plate', 'header_image_path' => 'static-pages/flat.jpg']);
        StaticPage::factory()->create(['slug' => 'datee', 'header_image_path' => 'static-pages/2026/07/dated.jpg']);
        StaticPage::factory()->create(['slug' => 'sans-image']);

        $paths = iterator_to_array((function () {
            yield from (new StaticPageMediaUsageProvider())->usedPaths();
        })());

        expect($paths)->toContain('static-pages/flat.jpg');
        expect($paths)->toContain('static-pages/2026/07/dated.jpg');
        expect($paths)->toHaveCount(2);
    });

    it('protects a referenced header image from GC while collecting an unclaimed one', function () {
        Storage::disk('public')->put('static-pages/used.jpg', 'x');
        Storage::disk('public')->put('static-pages/orphan.jpg', 'x');
        StaticPage::factory()->create(['slug' => 'utilisee', 'header_image_path' => 'static-pages/used.jpg']);

        app(\App\Domains\Media\Private\Services\MediaService::class)->gc(-1);

        Storage::disk('public')->assertExists('static-pages/used.jpg');
        Storage::disk('public')->assertMissing('static-pages/orphan.jpg');
    });
});

describe('Static page admin image endpoints authorization', function () {

    it('denies a non-admin posting an image payload to store', function () {
        $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
            ->post(route('static.admin.store'), staticPagePayload([
                'slug' => 'interdit',
                'header_image' => ['file' => UploadedFile::fake()->image('pic.jpg')],
            ]))
            ->assertRedirect(route('dashboard'));

        expect(StaticPage::where('slug', 'interdit')->exists())->toBeFalse();
    });

    it('denies a non-admin putting an image payload to update', function () {
        $page = StaticPage::factory()->create(['slug' => 'protegee', 'title' => 'Protégée']);

        $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
            ->put(route('static.admin.update', $page), staticPagePayload([
                'title' => 'Piratée',
                'slug' => 'protegee',
                'header_image' => ['path' => 'static-pages/whatever.jpg'],
            ]))
            ->assertRedirect(route('dashboard'));

        $page->refresh();
        expect($page->title)->toBe('Protégée');
        expect($page->header_image_path)->toBeNull();
    });
});
