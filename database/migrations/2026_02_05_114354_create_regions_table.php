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
        Schema::create('regions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('nom')->nullable();
            $table->string('niveau');
            $table->string('tel')->unique()->nullable();
            $table->string('photo')->nullable();

            $table->string('email')->unique();
            $table->string('password');

            $table->integer('role')->default(1);
            
            // Clé étrangère vers la table nations
            $table->uuid('nation_id');
            $table->foreign('nation_id')
                ->references('id')
                ->on('nations')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};