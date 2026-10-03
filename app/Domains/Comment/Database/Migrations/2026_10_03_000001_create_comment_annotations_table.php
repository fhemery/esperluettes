<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comment_annotations', function (Blueprint $table) {
            $table->id();
            // Always the root comment, also on reply rows
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            // NULL = root annotation; set = reply
            $table->foreignId('parent_annotation_id')->nullable()
                ->constrained('comment_annotations')->cascadeOnDelete();
            // Store author_id without enforcing a cross-domain FK
            $table->unsignedBigInteger('author_id')->nullable();
            $table->text('body');
            $table->text('highlighted_text')->nullable();
            $table->string('prefix', 255)->nullable();
            $table->string('suffix', 255)->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Explicit name: the generated one exceeds MySQL's 64-char limit
            $table->index(['comment_id', 'parent_annotation_id', 'deleted_at'], 'comment_annotations_tree_index');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_annotations');
    }
};
