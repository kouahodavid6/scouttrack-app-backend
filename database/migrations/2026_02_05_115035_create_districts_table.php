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
        Schema::create('districts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('nom')->nullable();
            $table->string('niveau');
            $table->string('tel')->unique()->nullable();
            $table->string('photo')->nullable();

            $table->string('email')->unique();
            $table->string('password');

            $table->integer('role')->default(1);
            
            // Clé étrangère vers la table regions
            $table->uuid('region_id');
            $table->foreign('region_id')
                ->references('id')
                ->on('regions')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};