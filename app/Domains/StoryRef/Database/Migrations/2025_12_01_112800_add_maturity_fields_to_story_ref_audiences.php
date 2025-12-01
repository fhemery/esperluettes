<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('story_ref_audiences', function (Blueprint $table) {
            $table->unsignedTinyInteger('threshold_age')->nullable()->after('order');
            $table->boolean('is_mature_audience')->default(false)->after('threshold_age');
        });
    }

    public function down(): void
    {
        Schema::table('story_ref_audiences', function (Blueprint $table) {
            $table->dropColumn(['threshold_age', 'is_mature_audience']);
        });
    }
};
