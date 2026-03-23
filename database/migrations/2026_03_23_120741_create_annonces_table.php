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
        Schema::create('annonces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titre');
            $table->text('contenu');
            $table->string('type')->default('annonce'); // annonce, actualite
            $table->string('created_by_id'); // ID du créateur
            $table->string('created_by_type'); // nation, region, district, groupe, cu
            $table->string('target_type'); // region, district, groupe, cu, jeune (selon niveau)
            $table->json('target_ids')->nullable(); // IDs spécifiques ou null pour tous
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['created_by_type', 'created_by_id']);
            $table->index(['target_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annonces');
    }
};
