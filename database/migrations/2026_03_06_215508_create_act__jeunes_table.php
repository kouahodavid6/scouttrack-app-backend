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
        Schema::create('act__jeunes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('statut')->nullable();

            $table->uuid('activite_id');
            $table->foreign('activite_id')
                ->references('id')
                ->on('activites')
                ->onDelete('cascade');

            $table->uuid('jeune_id');
            $table->foreign('jeune_id')
                ->references('id')
                ->on('jeunes')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('act__jeunes');
    }
};
