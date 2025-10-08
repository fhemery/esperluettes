<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Drop index first to allow table recreation
            if (Schema::hasTable('news')) {
                try {
                    Schema::table('news', function (Blueprint $table) {
                        $table->dropIndex('idx_news_created_by');
                    });
                } catch (Throwable $e) {
                    // Index may not exist in sqlite; ignore
                }
            }

            Schema::create('news_tmp', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary');
                $table->longText('content');
                $table->string('header_image_path')->nullable();
                $table->boolean('is_pinned')->default(false)->index();
                $table->unsignedInteger('display_order')->nullable()->index();
                $table->string('status');
                $table->string('meta_description')->nullable();
                $table->timestamp('published_at')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->index('created_by', 'idx_news_created_by');
                $table->timestamps();
                $table->index(['status', 'published_at']);
                $table->index(['is_pinned', 'display_order', 'published_at']);
            });

            DB::statement('INSERT INTO news_tmp (id, title, slug, summary, content, header_image_path, is_pinned, display_order, status, meta_description, published_at, created_by, created_at, updated_at)
                           SELECT id, title, slug, summary, content, header_image_path, is_pinned, display_order, status, meta_description, published_at, created_by, created_at, updated_at FROM news');

            Schema::drop('news');
            Schema::rename('news_tmp', 'news');
        } else {
            Schema::table('news', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::create('news_tmp', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary');
                $table->longText('content');
                $table->string('header_image_path')->nullable();
                $table->boolean('is_pinned')->default(false)->index();
                $table->unsignedInteger('display_order')->nullable()->index();
                $table->string('status');
                $table->string('meta_description')->nullable();
                $table->timestamp('published_at')->nullable()->index();
                $table->unsignedBigInteger('created_by');
                $table->index('created_by', 'idx_news_created_by');
                $table->timestamps();
                $table->index(['status', 'published_at']);
                $table->index(['is_pinned', 'display_order', 'published_at']);
            });

            DB::statement('INSERT INTO news_tmp (id, title, slug, summary, content, header_image_path, is_pinned, display_order, status, meta_description, published_at, created_by, created_at, updated_at)
                           SELECT id, title, slug, summary, content, header_image_path, is_pinned, display_order, status, meta_description, published_at, COALESCE(created_by, 0), created_at, updated_at FROM news');

            Schema::drop('news');
            Schema::rename('news_tmp', 'news');
        } else {
            DB::table('news')->whereNull('created_by')->update(['created_by' => 0]);
            Schema::table('news', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable(false)->change();
            });
        }
    }
};
