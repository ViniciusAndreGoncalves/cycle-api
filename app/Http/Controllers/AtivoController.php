<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ativo;

class AtivoController extends Controller
{
    /**
     * LISTAR (GET /api/ativos)
     * Pode receber um filtro ?busca=PETR para facilitar
     */
    public function index(Request $request)
    {
        $query = Ativo::with('categoria');

        if ($request->has('busca')) {
            $busca = $request->input('busca');
            $query->where(function ($q) use ($busca) {
                $q->where('ticker', 'like', "%$busca%")
                  ->orWhere('nome', 'like', "%$busca%");
            });
        }
        return $query->get();
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_ativo_id' => 'required|exists:categoria_ativos,id',
            'ticker' => 'required|string||max:6|unique:ativos,ticker',
            'nome' => 'required|string|max:100',
        ]);

        $ativo = Ativo::create($validated);

        return response()->json($ativo, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ativo $ativo)
    {
        return $ativo->load('categoria');
    }

    /**
     * Update the specified resource in storage.
     */
    
}
