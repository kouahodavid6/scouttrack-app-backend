// database/migrations/2026_04_01_014601_create_reponses_autorisations_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reponses_autorisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('demande_id');
            $table->uuid('parent_id');
            $table->uuid('jeune_id');
            $table->enum('reponse', ['oui', 'non']);
            $table->json('donnees_formulaire')->nullable();
            $table->string('signature')->nullable();
            $table->timestamps();

            $table->foreign('demande_id')->references('id')->on('demandes_autorisations')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade');
            $table->foreign('jeune_id')->references('id')->on('jeunes')->onDelete('cascade');
            
            $table->unique(['demande_id', 'parent_id', 'jeune_id']);
            $table->index('demande_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reponses_autorisations');
    }
};