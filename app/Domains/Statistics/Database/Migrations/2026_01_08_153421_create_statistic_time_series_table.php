<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statistic_time_series', function (Blueprint $table) {
            $table->id();
            $table->string('statistic_key');
            $table->string('scope_type')->default('global');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('granularity');
            $table->date('period_start');
            $table->decimal('value', 20, 4);
            $table->decimal('cumulative_value', 20, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['statistic_key', 'scope_type', 'scope_id', 'granularity', 'period_start'],
                'stat_ts_unique'
            );
            $table->index(['statistic_key', 'scope_type', 'scope_id', 'granularity'], 'stat_ts_query');
            $table->index('period_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistic_time_series');
    }
};
