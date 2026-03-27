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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('author_type'); // nation, region, district, groupe, cu, jeune, parent, visitor
            $table->string('author_id')->nullable(); // ID dans la table correspondante
            $table->string('author_name')->nullable(); // Pour les visiteurs (pseudo)
            $table->enum('context', ['public', 'private'])->default('public');
            $table->text('message')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('audio_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['author_type', 'author_id']);
            $table->index('context');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};