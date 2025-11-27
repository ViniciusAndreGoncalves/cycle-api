<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Migration: create_carteiras_table
    public function up(): void
    {
        Schema::create('carteiras', function (Blueprint $table) {
            $table->id();
            // Cria a coluna user_id e já faz o link com a tabela users
            // O 'constrained' garante que se o user não existir, dá erro.
            // O 'onDelete cascade' deleta a carteira se o usuário for deletado.
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nome', 40);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carteiras');
    }
};
