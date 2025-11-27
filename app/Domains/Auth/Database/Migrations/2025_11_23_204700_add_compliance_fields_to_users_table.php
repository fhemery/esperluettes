<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('email_verified_at');
            $table->boolean('is_under_15')->default(false)->after('terms_accepted_at');
            $table->timestamp('parental_authorization_verified_at')->nullable()->after('is_under_15');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_at', 'is_under_15', 'parental_authorization_verified_at']);
        });
    }
};
