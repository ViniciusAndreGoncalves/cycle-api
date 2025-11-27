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
    Schema::create('movimentacoes', function (Blueprint $table) {
        $table->id();
        
        // Ligações principais (apenas o ID direto)
        $table->foreignId('carteira_id')->constrained('carteiras')->onDelete('cascade');
        $table->foreignId('ativo_id')->constrained('ativos');

        // Tipo da operação
        $table->enum('tipo', ['Compra', 'Venda', 'Aporte', 'Resgate']);
        
        // DECIMAL 18,8 para suportar frações de Cripto (Satoshi)
        $table->decimal('quantidade', 18, 8);
        
        // Preço unitário no momento da compra
        $table->decimal('preco_unitario', 12, 2);
        
        $table->dateTime('data_movimentacao');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes');
    }
};
