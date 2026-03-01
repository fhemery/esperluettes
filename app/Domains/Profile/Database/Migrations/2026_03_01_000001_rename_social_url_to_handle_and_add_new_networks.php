<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_profiles', function (Blueprint $table) {
            $table->renameColumn('facebook_url', 'facebook_handle');
            $table->renameColumn('x_url', 'x_handle');
            $table->renameColumn('instagram_url', 'instagram_handle');
            $table->renameColumn('youtube_url', 'youtube_handle');
            $table->string('tiktok_handle')->nullable()->after('youtube_handle');
            $table->string('bluesky_handle')->nullable()->after('tiktok_handle');
            $table->string('mastodon_handle')->nullable()->after('bluesky_handle');
        });
    }

    public function down(): void
    {
        Schema::table('profile_profiles', function (Blueprint $table) {
            $table->renameColumn('facebook_handle', 'facebook_url');
            $table->renameColumn('x_handle', 'x_url');
            $table->renameColumn('instagram_handle', 'instagram_url');
            $table->renameColumn('youtube_handle', 'youtube_url');
            $table->dropColumn(['tiktok_handle', 'bluesky_handle', 'mastodon_handle']);
        });
    }
};
