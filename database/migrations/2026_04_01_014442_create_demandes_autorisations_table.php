// database/migrations/2026_04_01_014442_create_demandes_autorisations_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_autorisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cu_id');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->date('date_activite');
            $table->string('lieu');
            $table->json('texte_trous')->nullable();
            $table->enum('status', ['en_attente', 'terminee', 'annulee'])->default('en_attente');
            $table->timestamps();

            $table->foreign('cu_id')->references('id')->on('c_u_s')->onDelete('cascade');
            $table->index('cu_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_autorisations');
    }
};