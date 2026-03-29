<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            
            // Pour likeable_id et likeable_type en format UUID
            $table->uuidMorphs('likeable');

            // Pour l'auteur en format UUID
            $table->string('author_type')->nullable();
            $table->uuid('author_id')->nullable(); 
            
            $table->string('session_id')->nullable();
            $table->timestamps();

            // Index unique pour éviter les doublons de likes
            $table->unique(
                ['likeable_id', 'likeable_type', 'author_type', 'author_id'],
                'unique_auth_like'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};