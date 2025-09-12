<?php

use App\Domains\Admin\Filament\Resources\StoryRef\TriggerWarningResource\Pages\ListTriggerWarnings;
use App\Domains\StoryRef\Models\StoryRefTriggerWarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Ensure a valid locale is set for NumberFormatter used by Filament components
    set_testing_locale('en');
});

it('shows the Export CSV button on the Trigger Warnings list page', function () {
    // Create an admin user and authenticate on web guard
    $user = admin($this);

    $this->actingAs($user);

    assert_export_button($this, '/admin/story/trigger-warnings');
});

it('streams a valid CSV with expected headers and data', function () {
    // Seed a couple of trigger warnings
    $tw1 = StoryRefTriggerWarning::create([
        'name' => 'Violence',
        'description' => 'Physical harm or threats',
        'is_active' => true,
        'order' => 1,
    ]);

    $tw2 = StoryRefTriggerWarning::create([
        'name' => 'Hate Speech',
        'slug' => 'hate-speech',
        'description' => 'Abusive or threatening speech',
        'is_active' => false,
        'order' => 2,
    ]);

    $user = admin($this);

    $this->actingAs($user);

    // Stream CSV via helper using the List page's filtered query
    $stream = csv_stream_from_list_page(ListTriggerWarnings::class, [
        'id' => 'ID',
        'name' => __('admin::story.shared.name'),
        'slug' => __('admin::story.shared.slug'),
        'description' => __('admin::story.shared.description'),
        'is_active' => __('admin::story.shared.active'),
        'order' => 'Order',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ], 'test.csv');

    expect($stream)->toBeInstanceOf(StreamedResponse::class);

    $csv = capture_streamed_csv($stream);
    $rows = parse_csv_lines($csv);

    // Assert header row values
    assert_csv_header($rows, [
        'ID',
        __('admin::story.shared.name'),
        __('admin::story.shared.slug'),
        __('admin::story.shared.description'),
        __('admin::story.shared.active'),
        'Order',
        'Created At',
        'Updated At',
    ]);

    // Parse data rows
    $row1 = $rows[1] ?? [];
    $row2 = $rows[2] ?? [];

    // Ensure IDs match our seeded rows (order by PK in export)
    $ids = array_filter([intval($row1[0] ?? 0), intval($row2[0] ?? 0)]);
    expect($ids)->toContain($tw1->id, $tw2->id);

    // Spot check names present among the parsed rows
    $names = array_filter([$row1[1] ?? null, $row2[1] ?? null]);
    expect($names)->toContain('Violence');
    expect($names)->toContain('Hate Speech');
});
