<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 64)->index();
            $table->string('entity_id', 191)->index();
            // Store author_id without enforcing a cross-domain FK
            $table->unsignedBigInteger('author_id')->index();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entity_type', 'entity_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
