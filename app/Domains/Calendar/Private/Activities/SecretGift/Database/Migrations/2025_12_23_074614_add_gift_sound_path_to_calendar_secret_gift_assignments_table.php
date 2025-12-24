<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_secret_gift_assignments', function (Blueprint $table) {
            $table->string('gift_sound_path')->nullable()->after('gift_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_secret_gift_assignments', function (Blueprint $table) {
            $table->dropColumn('gift_sound_path');
        });
    }
};
