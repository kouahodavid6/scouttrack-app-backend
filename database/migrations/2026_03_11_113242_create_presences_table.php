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
        Schema::create('presences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_present')->nullable();

            $table->uuid('jeune_id');
            $table->foreign('jeune_id')
                ->references('id')
                ->on('jeunes')
                ->onDelete('cascade');

            $table->uuid('reunion_id');
            $table->foreign('reunion_id')
                ->references('id')
                ->on('reunions')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
