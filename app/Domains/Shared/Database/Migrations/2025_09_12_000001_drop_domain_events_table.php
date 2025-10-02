<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to drop the legacy 'domain_events' table.
 * Has been replaced by the Events module and the events_domain table.
 * 
 * The structure is globally the same, it is just a matter of clean up and naming.
 */

return new class extends Migration {
    public function up(): void
    {
        // Remove the legacy 'domain_events' table if it exists.
        if (Schema::hasTable('domain_events')) {
            Schema::drop('domain_events');
        }
    }

    public function down(): void
    {
        // Recreate the legacy table to allow rollback.
        if (! Schema::hasTable('domain_events')) {
            Schema::create('domain_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 255);
                $table->json('payload');
                $table->unsignedBigInteger('triggered_by_user_id')->nullable()->index();
                $table->string('context_ip', 45)->nullable();
                $table->string('context_user_agent', 512)->nullable();
                $table->string('context_url', 1024)->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('occurred_at')->useCurrent()->index();
                $table->timestamps();
            });
        }
    }
};
