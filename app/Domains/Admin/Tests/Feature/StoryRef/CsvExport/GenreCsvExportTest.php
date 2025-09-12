<?php

use App\Domains\Admin\Filament\Resources\StoryRef\GenreResource\Pages\ListGenres;
use App\Domains\StoryRef\Models\StoryRefGenre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    set_testing_locale('en');
});

it('shows the Export CSV button on the Genres list page', function () {
    $user = admin($this);
    $this->actingAs($user);

    assert_export_button($this, '/admin/story/genres');
});

it('streams a valid CSV with expected headers and data', function () {
    $g1 = StoryRefGenre::create([
        'name' => 'Fantasy',
        'slug' => 'fantasy',
        'description' => 'Magic and adventure',
        'is_active' => true,
        'order' => 1,
    ]);

    $g2 = StoryRefGenre::create([
        'name' => 'Sci-Fi',
        'slug' => 'sci-fi',
        'description' => 'Science and fiction',
        'is_active' => false,
        'order' => 2,
    ]);

    $user = admin($this);
    $this->actingAs($user);

    $columns = [
        'id' => 'ID',
        'name' => __('admin::story.shared.name'),
        'slug' => __('admin::story.shared.slug'),
        'description' => __('admin::story.shared.description'),
        'is_active' => __('admin::story.shared.active'),
        'order' => 'Order',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ];

    $stream = csv_stream_from_list_page(ListGenres::class, $columns, 'genres.csv');
    expect($stream)->toBeInstanceOf(StreamedResponse::class);

    $csv = capture_streamed_csv($stream);
    $rows = parse_csv_lines($csv);

    assert_csv_header($rows, array_values($columns));

    $row1 = $rows[1] ?? [];
    $row2 = $rows[2] ?? [];
    $ids = array_filter([intval($row1[0] ?? 0), intval($row2[0] ?? 0)]);
    expect($ids)->toContain($g1->id, $g2->id);

    $names = array_filter([$row1[1] ?? null, $row2[1] ?? null]);
    expect($names)->toContain('Fantasy');
    expect($names)->toContain('Sci-Fi');
});
