<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes';

    protected $fillable = [
        'carteira_id',
        'ativo_id',
        'tipo',
        'quantidade',
        'preco_unitario',
        'data_movimentacao'
    ];

    public function carteira()
    {
        return $this->belongsTo(Carteira::class);
    }

    public function ativo()
    {
        return $this->belongsTo(Ativo::class);
    }
}
