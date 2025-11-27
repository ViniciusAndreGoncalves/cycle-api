<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carteira extends Model
{
    use HasFactory;

    // Campos que podem ser preenchidos em massa (Mass Assignment)
    protected $fillable = ['user_id', 'nome'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movimentacoes()
    {
        return $this->hasMany(Movimentacao::class);
    }
}
