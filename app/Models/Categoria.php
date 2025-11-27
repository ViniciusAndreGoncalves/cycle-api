<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'nome', 'cor'];

    // Relacionamento: Categoria pertence a um User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento: Uma categoria tem várias Despesas
    public function despesas()
    {
        return $this->hasMany(Despesa::class);
    }
}
