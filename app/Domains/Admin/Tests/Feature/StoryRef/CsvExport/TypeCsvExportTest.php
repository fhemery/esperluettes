<?php

use App\Domains\Admin\Filament\Resources\StoryRef\TypeResource\Pages\ListTypes;
use App\Domains\StoryRef\Private\Models\StoryRefType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    set_testing_locale('en');
});

it('shows the Export CSV button on the Types list page', function () {
    $user = admin($this);
    $this->actingAs($user);

    assert_export_button($this, '/admin/story/types');
});

it('streams a valid CSV with expected headers and data', function () {
    $t1 = StoryRefType::create([
        'name' => 'Short Story',
        'slug' => 'short-story',
        'is_active' => true,
        'order' => 1,
    ]);

    $t2 = StoryRefType::create([
        'name' => 'Novel',
        'slug' => 'novel',
        'is_active' => false,
        'order' => 2,
    ]);

    $user = admin($this);
    $this->actingAs($user);

    $columns = [
        'id' => 'ID',
        'name' => __('admin::story.shared.name'),
        'slug' => __('admin::story.shared.slug'),
        'is_active' => __('admin::story.shared.active'),
        'order' => 'Order',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ];

    $stream = csv_stream_from_list_page(ListTypes::class, $columns, 'types.csv');
    expect($stream)->toBeInstanceOf(StreamedResponse::class);

    $csv = capture_streamed_csv($stream);
    $rows = parse_csv_lines($csv);

    assert_csv_header($rows, array_values($columns));

    $row1 = $rows[1] ?? [];
    $row2 = $rows[2] ?? [];
    $ids = array_filter([intval($row1[0] ?? 0), intval($row2[0] ?? 0)]);
    expect($ids)->toContain($t1->id, $t2->id);

    $names = array_filter([$row1[1] ?? null, $row2[1] ?? null]);
    expect($names)->toContain('Short Story');
    expect($names)->toContain('Novel');
});
