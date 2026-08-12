<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_activities', function (Blueprint $table) {
            $table->dropColumn(['requires_subscription', 'max_participants']);
        });
    }

    public function down(): void
    {
        Schema::table('calendar_activities', function (Blueprint $table) {
            $table->boolean('requires_subscription')->default(false);
            $table->integer('max_participants')->nullable();
        });
    }
};
