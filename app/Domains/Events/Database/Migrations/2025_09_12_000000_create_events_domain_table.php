<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events_domain', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->index();
            $table->json('payload');
            $table->unsignedBigInteger('triggered_by_user_id')->nullable()->index();
            $table->string('context_ip', 45)->nullable();
            $table->string('context_user_agent', 512)->nullable();
            $table->string('context_url', 2048)->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('occurred_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_domain');
    }
};
