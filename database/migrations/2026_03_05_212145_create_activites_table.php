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
        Schema::create('activites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom_act');
            $table->string('description');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('badge')->nullable();

            $table->uuid('etape_id');
            $table->foreign('etape_id')
                ->references('id')
                ->on('etapes')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activites');
    }
};