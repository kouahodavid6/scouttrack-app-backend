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
        Schema::create('c_u_s', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('nom')->nullable();
            $table->string('niveau');
            $table->string('tel')->unique()->nullable();
            $table->string('photo')->nullable();

            $table->string('email')->unique();
            $table->string('password');

            $table->integer('role')->default(1);
            
            // Clé étrangère vers la table groupes
            $table->uuid('groupe_id');
            $table->foreign('groupe_id')
                ->references('id')
                ->on('groupes')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_u_s');
    }
};