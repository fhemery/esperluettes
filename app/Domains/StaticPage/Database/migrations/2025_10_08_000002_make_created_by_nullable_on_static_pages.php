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
            Schema::table('static_pages', function (Blueprint $table) {
                $table->dropIndex('idx_static_pages_created_by');
            });
            // Recreate table with created_by nullable
            Schema::create('static_pages_tmp', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary')->nullable();
                $table->longText('content');
                $table->string('header_image_path')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->string('meta_description')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->index('created_by', 'idx_static_pages_created_by');
            });

            DB::statement('INSERT INTO static_pages_tmp (id, title, slug, summary, content, header_image_path, status, meta_description, published_at, created_at, updated_at, created_by)
                           SELECT id, title, slug, summary, content, header_image_path, status, meta_description, published_at, created_at, updated_at, created_by FROM static_pages');

            Schema::drop('static_pages');
            Schema::rename('static_pages_tmp', 'static_pages');
        } else {
            Schema::table('static_pages', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Recreate table with created_by NOT NULL, coalescing nulls to 0
            Schema::create('static_pages_tmp', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary')->nullable();
                $table->longText('content');
                $table->string('header_image_path')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->string('meta_description')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->unsignedBigInteger('created_by');
                $table->index('created_by', 'idx_static_pages_created_by');
            });

            DB::statement('INSERT INTO static_pages_tmp (id, title, slug, summary, content, header_image_path, status, meta_description, published_at, created_at, updated_at, created_by)
                           SELECT id, title, slug, summary, content, header_image_path, status, meta_description, published_at, created_at, updated_at, COALESCE(created_by, 0) FROM static_pages');

            Schema::drop('static_pages');
            Schema::rename('static_pages_tmp', 'static_pages');
        } else {
            DB::table('static_pages')->whereNull('created_by')->update(['created_by' => 0]);
            Schema::table('static_pages', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable(false)->change();
            });
        }
    }
};
