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
        Schema::create('jeunes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('nom')->nullable();
            $table->date('date_naissance');
            $table->string('tel')->unique()->nullable();
            $table->string('photo')->nullable();

            $table->string('email')->unique();
            $table->string('password');

            $table->integer('role')->default(1);
            
            // Clé étrangères
            $table->uuid('cu_id');
            $table->foreign('cu_id')
                ->references('id')
                ->on('c_u_s')
                ->onDelete('cascade');

            $table->uuid('branche_id');
            $table->foreign('branche_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jeunes');
    }
};