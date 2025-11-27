<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaAtivo extends Model
{
    use HasFactory;

    // Forçamos o nome da tabela para garantir que o Laravel ache
    protected $table = 'categorias_ativos';

    protected $fillable = ['nome'];

    // Relacionamento: Uma categoria de ativo tem muitos Ativos (Ex: Ações tem PETR4, VALE3...)
    public function ativos()
    {
        // Especificamos 'categoria_ativo_id' para garantir que ele ache a chave correta
        return $this->hasMany(Ativo::class, 'categoria_ativo_id');
    }
}