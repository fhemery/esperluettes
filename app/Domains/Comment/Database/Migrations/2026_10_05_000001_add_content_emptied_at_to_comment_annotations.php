<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comment_annotations', function (Blueprint $table) {
            // Set when a moderator empties the body; the row stays editable by nobody
            $table->timestamp('content_emptied_at')->nullable()->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('comment_annotations', function (Blueprint $table) {
            $table->dropColumn('content_emptied_at');
        });
    }
};
