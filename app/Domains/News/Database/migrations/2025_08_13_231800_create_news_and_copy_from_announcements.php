<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary');
                $table->longText('content');
                $table->string('header_image_path')->nullable();
                $table->boolean('is_pinned')->default(false)->index();
                $table->unsignedInteger('display_order')->nullable()->index();
                $table->string('status'); // draft | published
                $table->string('meta_description')->nullable();
                $table->timestamp('published_at')->nullable()->index();
                $table->unsignedBigInteger('created_by');
                $table->index('created_by', 'idx_news_created_by');
                $table->timestamps();

                // Additional helpful indexes
                $table->index(['status', 'published_at']);
                $table->index(['is_pinned', 'display_order', 'published_at']);
            });
        }

        // Copy data from announcements to news if announcements exists
        if (Schema::hasTable('announcements')) {

            // Use raw SQL to stay compatible with SQLite and MySQL.
            DB::statement(
                "INSERT INTO news SELECT * FROM announcements"
            );
            Schema::drop('announcements');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('news')) {
            Schema::drop('news');
        }
    }
};
