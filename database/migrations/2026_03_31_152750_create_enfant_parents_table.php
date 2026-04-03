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
        Schema::create('enfant_parents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('jeune_id');
            $table->uuid('parent_id');
            $table->enum('lien', ['pere', 'mere', 'tuteur'])->default('tuteur');
            $table->boolean('autorisation_camp')->default(false);
            $table->json('autorisations')->nullable();
            $table->timestamps();

            $table->foreign('jeune_id')->references('id')->on('jeunes')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade');
            $table->unique(['jeune_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enfant_parents');
    }
};
