<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Support\LegacyGiftImageMover;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Data-only migration: gift images move from the `local` disk to Media's
 * private disk. Tied to `artisan migrate` on purpose — the serve route resolves
 * the disk from the path prefix, so code and data have to flip together or every
 * pre-existing gift image 404s. The logic itself lives in LegacyGiftImageMover.
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info(
            'Secret Gift: gift images moved to the Media private disk',
            app(LegacyGiftImageMover::class)->toMedia(),
        );
    }

    public function down(): void
    {
        Log::info(
            'Secret Gift: gift images moved back to the local disk',
            app(LegacyGiftImageMover::class)->toLegacy(),
        );
    }
};
