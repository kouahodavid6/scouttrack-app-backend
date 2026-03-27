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
        Schema::create('commentaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->string('author_type'); // nation, region, district, groupe, cu, jeune, parent, visitor
            $table->string('author_id')->nullable();
            $table->string('author_name')->nullable();
            $table->text('message')->nullable();
            $table->string('video_url')->nullable();
            $table->string('audio_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->index(['author_type', 'author_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commentaires');
    }
};
