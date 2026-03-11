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
        Schema::create('reunions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date_reunion');
            $table->time('heure_debut');
            $table->time('heure_fin');

            $table->uuid('cu_id');
            $table->foreign('cu_id')
                ->references('id')
                ->on('c_u_s')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reunions');
    }
};
