<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('domain_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->json('payload');
            $table->unsignedBigInteger('triggered_by_user_id')->nullable()->index();
            $table->string('context_ip', 45)->nullable();
            $table->string('context_user_agent', 512)->nullable();
            $table->string('context_url', 1024)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_events');
    }
};
