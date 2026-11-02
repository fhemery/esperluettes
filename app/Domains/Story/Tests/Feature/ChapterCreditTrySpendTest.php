<?php

declare(strict_types=1);

use App\Domains\Story\Private\Services\ChapterCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->credits = app(ChapterCreditService::class);
    $this->user = alice($this);
    setAvailableCredits($this->user->id, 3);
});

describe('ChapterCreditService::trySpend', function () {

    it('spends the exact balance', function () {
        expect($this->credits->trySpend($this->user->id, 3))->toBeTrue();
        expect($this->credits->availableForUser($this->user->id))->toBe(0);
    });

    it('refuses an amount over the balance and leaves the row unchanged', function () {
        expect($this->credits->trySpend($this->user->id, 4))->toBeFalse();
        expect($this->credits->availableForUser($this->user->id))->toBe(3);
    });

    it('treats an amount of zero as a no-op', function () {
        DB::enableQueryLog();
        expect($this->credits->trySpend($this->user->id, 0))->toBeTrue();
        expect(DB::getQueryLog())->toBeEmpty();
        expect($this->credits->availableForUser($this->user->id))->toBe(3);
    });

    it('returns false and creates nothing when the user has no row', function () {
        $unknownId = $this->user->id + 999;
        expect($this->credits->trySpend($unknownId, 1))->toBeFalse();
        expect(DB::table('story_chapter_credits')->where('user_id', $unknownId)->exists())->toBeFalse();
    });
});
