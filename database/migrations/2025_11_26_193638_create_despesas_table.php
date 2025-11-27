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
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            // Liga com a tabela categorias (de despesa)
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->string('descricao', 255);
            // DECIMAL 10,2 para dinheiro (até 99 milhões)
            $table->decimal('valor', 10, 2);
            $table->dateTime('data_despesa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};
