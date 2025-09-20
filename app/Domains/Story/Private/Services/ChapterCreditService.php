<?php

namespace App\Domains\Story\Private\Services;

use Illuminate\Support\Facades\DB;
use App\Domains\Events\Public\Api\EventBus;

class ChapterCreditService
{
    public function __construct(
        private readonly EventBus $events,
    ) {}

    /**
     * Return available credits for a user: gained - spent. Can be negative for legacy overuse.
     */
    public function availableForUser(int $userId): int
    {
        $row = DB::table('story_chapter_credits')->where('user_id', $userId)->first();
        if (!$row) return 0;
        return (int) $row->credits_gained - (int) $row->credits_spent;
    }

    private function hasRow(int $userId): bool
    {
        return DB::table('story_chapter_credits')->where('user_id', $userId)->exists();
    }

    public function grantInitialOnRegistration(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            if ($this->hasRow($userId)) {
                return;
            }

            DB::table('story_chapter_credits')
                ->insert([
                    'user_id' => $userId,
                    'credits_gained' => 5,
                    'credits_spent' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        });
    }

    public function grantOne(int $userId): void
    {
        if (!$this->hasRow($userId)) {
            $this->grantInitialOnRegistration($userId);
        }
        DB::table('story_chapter_credits')
            ->where('user_id', $userId)
            ->update([
                'credits_gained' => DB::raw('credits_gained + 1'),
                'updated_at' => now(),
            ]);
    }

    public function spendOne(int $userId): void
    {
        if (!$this->hasRow($userId)) {
            $this->grantInitialOnRegistration($userId);
        }
        DB::table('story_chapter_credits')
            ->where('user_id', $userId)
            ->update(['credits_spent' => DB::raw('credits_spent + 1')]);
    }
}
