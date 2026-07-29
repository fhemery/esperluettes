<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Private\Support\ActivityMediaUsageProvider;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    registerFakeActivityType(app(CalendarRegistry::class));
});

describe('Activity image via Media', function () {

    it('stores an upload flat under activities and persists the path', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), [
                'name' => 'Activité avec image',
                'activity_type' => 'fake',
                'image' => ['file' => UploadedFile::fake()->image('pic.jpg', 800, 600)],
            ])
            ->assertRedirect(route('calendar.admin.activities.index'));

        $activity = Activity::where('name', 'Activité avec image')->firstOrFail();

        expect($activity->image_path)->toStartWith('activities/');
        // Flat, not dated: exactly one segment after the folder.
        expect($activity->image_path)->not->toMatch('#^activities/\d{4}/\d{2}/#');
        expect(substr_count($activity->image_path, '/'))->toBe(1);
        Storage::disk('public')->assertExists($activity->image_path);
    });

    it('reuses an existing path on update and stores no new file', function () {
        Storage::disk('public')->put('activities/existing.jpg', 'x');
        $activityId = createActivity($this, ['name' => 'Réutilisation']);
        $activity = Activity::findOrFail($activityId);

        $before = count(Storage::disk('public')->allFiles());

        $this->actingAs(admin($this))
            ->put(route('calendar.admin.activities.update', $activity), [
                'name' => 'Réutilisation',
                'image' => ['path' => 'activities/existing.jpg'],
            ])
            ->assertRedirect(route('calendar.admin.activities.index'));

        $activity->refresh();
        expect($activity->image_path)->toBe('activities/existing.jpg');
        expect(Storage::disk('public')->allFiles())->toHaveCount($before);
    });

    it('clears the image path on removal but leaves the file on disk', function () {
        Storage::disk('public')->put('activities/kept.jpg', 'x');
        $activityId = createActivity($this, [
            'name' => 'Suppression',
            'image_path' => 'activities/kept.jpg',
        ]);
        $activity = Activity::findOrFail($activityId);

        $this->actingAs(admin($this))
            ->put(route('calendar.admin.activities.update', $activity), [
                'name' => 'Suppression',
                'image' => ['path' => ''],
            ])
            ->assertRedirect(route('calendar.admin.activities.index'));

        $activity->refresh();
        expect($activity->image_path)->toBeNull();
        Storage::disk('public')->assertExists('activities/kept.jpg');
    });

    it('replaces the image with a new upload without deleting the previous file', function () {
        Storage::disk('public')->put('activities/previous.jpg', 'x');
        $activityId = createActivity($this, [
            'name' => 'Remplacement',
            'image_path' => 'activities/previous.jpg',
        ]);
        $activity = Activity::findOrFail($activityId);

        $this->actingAs(admin($this))
            ->put(route('calendar.admin.activities.update', $activity), [
                'name' => 'Remplacement',
                'image' => ['file' => UploadedFile::fake()->image('new.jpg', 800, 600)],
            ])
            ->assertRedirect(route('calendar.admin.activities.index'));

        $activity->refresh();
        expect($activity->image_path)->not->toBe('activities/previous.jpg');
        Storage::disk('public')->assertExists('activities/previous.jpg');
    });
});

describe('ActivityMediaUsageProvider', function () {

    it('reports every non-null image path, grandfathered dated ones included', function () {
        createActivity($this, ['name' => 'Plate', 'image_path' => 'activities/flat.jpg']);
        createActivity($this, ['name' => 'Datée', 'image_path' => 'activities/2026/07/dated.jpg']);
        createActivity($this, ['name' => 'Sans image']);

        $paths = iterator_to_array((function () {
            yield from (new ActivityMediaUsageProvider())->usedPaths();
        })());

        expect($paths)->toContain('activities/flat.jpg');
        expect($paths)->toContain('activities/2026/07/dated.jpg');
        expect($paths)->toHaveCount(2);
    });

    it('protects a referenced activity image from GC while collecting an unclaimed one', function () {
        Storage::disk('public')->put('activities/used.jpg', 'x');
        Storage::disk('public')->put('activities/orphan.jpg', 'x');
        createActivity($this, ['name' => 'Utilisée', 'image_path' => 'activities/used.jpg']);

        app(\App\Domains\Media\Private\Services\MediaService::class)->gc(-1);

        Storage::disk('public')->assertExists('activities/used.jpg');
        Storage::disk('public')->assertMissing('activities/orphan.jpg');
    });
});

describe('Activity admin image endpoints authorization', function () {

    it('denies a non-admin posting an image payload to store', function () {
        $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
            ->post(route('calendar.admin.activities.store'), [
                'name' => 'Interdit',
                'activity_type' => 'fake',
                'image' => ['file' => UploadedFile::fake()->image('pic.jpg')],
            ])
            ->assertRedirect();

        expect(Activity::where('name', 'Interdit')->exists())->toBeFalse();
    });

    it('denies a non-admin putting an image payload to update', function () {
        $activityId = createActivity($this, ['name' => 'Protégée']);
        $activity = Activity::findOrFail($activityId);

        $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
            ->put(route('calendar.admin.activities.update', $activity), [
                'name' => 'Piratée',
                'image' => ['path' => 'activities/whatever.jpg'],
            ])
            ->assertRedirect();

        $activity->refresh();
        expect($activity->name)->toBe('Protégée');
        expect($activity->image_path)->toBeNull();
    });
});
