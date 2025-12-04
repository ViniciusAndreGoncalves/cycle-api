<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ativo extends Model
{
    use HasFactory;

    protected $fillable = [
        'categoria_ativo_id',
        'ticker',
        'nome',
        'url_logo',
        'preco_atual'
    ];

    public function categoria()
    {
        //especificar o nome da coluna FK, pois foge do padrão inglês
        return $this->belongsTo(CategoriaAtivo::class, 'categoria_ativo_id');
    }

    public function movimentacoes()
    {
        // Isso diz: "Um Ativo tem muitas Movimentações"
        return $this->hasMany(Movimentacao::class);
    }
}
