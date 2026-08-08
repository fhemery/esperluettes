<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_secret_gift_settings', function (Blueprint $table) {
            $table->id();
            // One settings row per Secret Gift, and the unique index is also the
            // only lookup key.
            $table->foreignId('activity_id')->unique()->constrained('calendar_activities')->cascadeOnDelete();
            // Mandatory on the admin form: enrolment closes here, whatever the
            // moderator does. A row without it cannot exist.
            $table->dateTime('registration_ends_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_secret_gift_settings');
    }
};
