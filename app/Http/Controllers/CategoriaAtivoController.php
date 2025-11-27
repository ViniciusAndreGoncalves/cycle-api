<?php
/*
* as categorias de ativos ("Ações", "Renda Fixa", "Criptomoedas") 
* são do Sistema, não do usuário. É um catálogo fixo para 
* padronizar.
*/

namespace App\Http\Controllers;

use App\Models\CategoriaAtivo;
use Illuminate\Http\Request;

class CategoriaAtivoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CategoriaAtivo::all();
    }

}
