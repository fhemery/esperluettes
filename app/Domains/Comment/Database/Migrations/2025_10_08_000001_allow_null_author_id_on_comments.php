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
            // Recreate table with author_id nullable
            Schema::create('comments_tmp', function (Blueprint $table) {
                $table->id();
                $table->string('commentable_type', 64)->index();
                $table->unsignedBigInteger('commentable_id')->index();
                $table->unsignedBigInteger('author_id')->nullable()->index();
                $table->unsignedBigInteger('parent_comment_id')->nullable()->index();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_answered')->default(false);
                $table->text('body');
                $table->timestamp('edited_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['commentable_type', 'commentable_id', 'created_at']);
            });

            // Copy data
            DB::statement('INSERT INTO comments_tmp (id, commentable_type, commentable_id, author_id, parent_comment_id, is_active, is_answered, body, edited_at, created_at, updated_at, deleted_at)
                           SELECT id, commentable_type, commentable_id, author_id, parent_comment_id, is_active, is_answered, body, edited_at, created_at, updated_at, deleted_at FROM comments');

            Schema::drop('comments');
            Schema::rename('comments_tmp', 'comments');
        } else {
            Schema::table('comments', function (Blueprint $table) {
                $table->unsignedBigInteger('author_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Recreate table with author_id NOT NULL, coalescing nulls to 0
            Schema::create('comments_tmp', function (Blueprint $table) {
                $table->id();
                $table->string('commentable_type', 64)->index();
                $table->unsignedBigInteger('commentable_id')->index();
                $table->unsignedBigInteger('author_id')->index();
                $table->unsignedBigInteger('parent_comment_id')->nullable()->index();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_answered')->default(false);
                $table->text('body');
                $table->timestamp('edited_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['commentable_type', 'commentable_id', 'created_at']);
            });

            DB::statement('INSERT INTO comments_tmp (id, commentable_type, commentable_id, author_id, parent_comment_id, is_active, is_answered, body, edited_at, created_at, updated_at, deleted_at)
                           SELECT id, commentable_type, commentable_id, COALESCE(author_id, 0), parent_comment_id, is_active, is_answered, body, edited_at, created_at, updated_at, deleted_at FROM comments');

            Schema::drop('comments');
            Schema::rename('comments_tmp', 'comments');
        } else {
            // Ensure no nulls before making NOT NULL
            DB::table('comments')->whereNull('author_id')->update(['author_id' => 0]);
            Schema::table('comments', function (Blueprint $table) {
                $table->unsignedBigInteger('author_id')->nullable(false)->change();
            });
        }
    }
};
