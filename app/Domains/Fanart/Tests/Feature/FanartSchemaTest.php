<?php

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('persists a fanart with kind/status enums and soft-deletes it', function () {
    $fanart = createFanart([
        'kind' => FanartKind::Audio,
        'status' => FanartStatus::Pending,
        'chapter_id' => 7,
        'share_on_discord' => true,
    ]);

    $fresh = Fanart::findOrFail($fanart->id);
    expect($fresh->kind)->toBe(FanartKind::Audio)
        ->and($fresh->status)->toBe(FanartStatus::Pending)
        ->and($fresh->is_artwork)->toBeFalse()
        ->and($fresh->share_on_discord)->toBeTrue()
        ->and($fresh->published_at)->not->toBeNull();

    $fanart->delete();

    expect(Fanart::find($fanart->id))->toBeNull()
        ->and(Fanart::withTrashed()->find($fanart->id))->not->toBeNull();
    $this->assertDatabaseHas('fanarts', ['id' => $fanart->id, 'kind' => 'audio', 'status' => 'pending']);
});

it('enforces one fanartist row per (story, user)', function () {
    Fanartist::create(['story_id' => 3, 'user_id' => 9, 'granted_by_user_id' => 1]);

    expect(fn () => Fanartist::create(['story_id' => 3, 'user_id' => 9, 'granted_by_user_id' => 2]))
        ->toThrow(QueryException::class);

    Fanartist::create(['story_id' => 3, 'user_id' => 10, 'granted_by_user_id' => 1]);
    Fanartist::create(['story_id' => 4, 'user_id' => 9, 'granted_by_user_id' => 1]);

    expect(Fanartist::count())->toBe(3);
});
