<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('commentable_type', 64)->index();
            $table->unsignedBigInteger('commentable_id')->index();
            // Store author_id without enforcing a cross-domain FK
            $table->unsignedBigInteger('author_id')->index();
            // Parent for one-level replies (null for roots)
            $table->unsignedBigInteger('parent_comment_id')->nullable()->index();
            // Moderation/answering state
            $table->boolean('is_active')->default(true);
            $table->boolean('is_answered')->default(false);
            $table->text('body');
            // Track edits separate from updated_at semantics
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
