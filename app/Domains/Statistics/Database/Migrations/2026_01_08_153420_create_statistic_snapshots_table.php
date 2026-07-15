<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statistic_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('statistic_key');
            $table->string('scope_type')->default('global');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->decimal('value', 20, 4);
            $table->json('metadata')->nullable();
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['statistic_key', 'scope_type', 'scope_id'], 'stat_snapshot_unique');
            $table->index(['scope_type', 'scope_id']);
            $table->index('statistic_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistic_snapshots');
    }
};
