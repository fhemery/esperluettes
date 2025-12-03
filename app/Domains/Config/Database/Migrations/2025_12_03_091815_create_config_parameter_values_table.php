<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('config_parameter_values', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 50);
            $table->string('key', 100);
            $table->text('value');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['domain', 'key']);
            $table->index('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_parameter_values');
    }
};
