<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Before this fix a reader deleting a quote only soft-deleted it, so a later
 * deactivation then reactivation of the reader brought it back. Reader
 * deletions are now hard deletes, which makes a soft-deleted row mean "owner
 * currently deactivated".
 *
 * Production has no deactivated user at deployment time, so every soft-deleted
 * row is a former reader deletion and is purged here.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('quotes')->whereNotNull('deleted_at')->delete();
    }

    public function down(): void
    {
        // No-op: purged rows cannot be recreated.
    }
};
