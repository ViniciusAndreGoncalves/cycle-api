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
        Schema::create('ativos', function (Blueprint $table) {
            $table->id();
            // Liga com a tabela categorias_ativos migration acima
            $table->foreignId('categoria_ativo_id')->constrained('categorias_ativos');
            $table->string('ticker', 6); // Ex: PETR4, BTC
            $table->string('nome', 30);  // Ex: Petrobras PN
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ativos');
    }
};
