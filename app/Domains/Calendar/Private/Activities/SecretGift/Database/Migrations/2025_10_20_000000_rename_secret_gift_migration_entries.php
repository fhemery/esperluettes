<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

/**
 * Intentionally a no-op.
 *
 * The Secret Gift create migrations were renamed from 2024_12_13_* to
 * 2025_10_20_000001 / 000002. Rewriting rows in `migrations` here does not
 * help: Migrator builds the pending list before any `up()` runs, so the
 * renamed creates still execute in the same batch. Those creates now guard
 * with Schema::hasTable() instead.
 *
 * Kept as an empty migration so environments that already logged this file
 * name stay consistent.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
