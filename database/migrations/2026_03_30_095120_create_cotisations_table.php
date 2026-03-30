<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('montant', 10, 0);
            $table->enum('type', ['nationale', 'locale'])->default('locale');
            $table->string('created_by_type');
            $table->string('created_by_id');
            $table->date('date_limite')->nullable();
            $table->enum('statut', ['active', 'terminee', 'annulee'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['created_by_type', 'created_by_id']);
            $table->index('type');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotisations');
    }
};