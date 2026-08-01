<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            // Source of truth for advanced (MultiEdit) pages: an ordered array
            // of typed blocks. NULL ⇒ simple page (content is the author HTML).
            // For advanced pages, `content` is reused as a rendered-HTML cache.
            $table->json('content_blocks')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            $table->dropColumn('content_blocks');
        });
    }
};
