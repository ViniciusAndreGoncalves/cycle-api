<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Despesa extends Model
{
    use HasFactory;

    protected $fillable = [
        'categoria_id', 
        'descricao', 
        'valor', 
        'data_despesa'
    ];

    // Isso converte automaticamente o texto do banco para um objeto de Data no PHP
    protected $casts = [
        'data_despesa' => 'datetime',
        'valor' => 'decimal:2' // Garante que venha como número, não string
    ];

    // Relacionamento: Despesa pertence a uma Categoria
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}