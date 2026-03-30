// database/migrations/2026_03_30_000002_create_paiements_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cotisation_id');
            $table->string('jeune_id');
            $table->string('jeune_nom');
            $table->string('jeune_email')->nullable();
            $table->string('transaction_id')->unique();
            $table->decimal('montant', 10, 0);
            $table->string('numero_telephone')->nullable();
            $table->enum('statut', ['en_attente', 'paye', 'echoue'])->default('en_attente');
            $table->json('kkiapay_response')->nullable();
            $table->timestamp('date_paiement')->nullable();
            $table->timestamps();
            
            $table->foreign('cotisation_id')->references('id')->on('cotisations')->onDelete('cascade');
            $table->index('jeune_id');
            $table->index('transaction_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};