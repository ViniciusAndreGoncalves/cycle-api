<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Despesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DespesaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $despesas = Despesa::whereHas('categoria', function ($query) {
            $query->where('user_id', Auth::id());
        })->with('categoria')->latest('data_despesa')->get();

        return $despesas;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|gt:0',
            'data_despesa' => 'required|date',
        ]);

        $categoria = Categoria::find($validated['categoria_id']);
        if ($categoria->user_id !== Auth::id()){
            return response()->json(['messege' => 'Essa categoria não é sua!'], 403);
        }
        $despesa = Despesa::create($validated);

        return response()->json($despesa->load('categoria'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Despesa $despesa)
    {
        if ($despesa->categoria->user_id !== Auth::id()){
            return response()->json(['messege' => 'Acesso negado'], 403);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Despesa $despesa)
    {
        if ($despesa->categoria->user_id !== Auth::id()){
            return response()->json(['messege' => 'Acesso negado'], 403);
        }

        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|gt:0',
            'data_despesa' => 'required|date',
        ]);

        if (isset($validated['categoria_id'])) {
            $novaCat = Categoria::find($validated['categoria_id']);
            if ($novaCat->user_id !== Auth::id()){
                return response()->json(['messege' => 'Categoria inválida!'], 403);
            }
        }

        $despesa->update($validated);
        
        return $despesa->load('categoria');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Despesa $despesa)
    {
        if ($despesa->categoria->user_id !== Auth::id()){
            return response()->json(['messege' => 'Acesso negado'], 403);
        }

        $despesa->delete();
        return response()->noContent();
    }
}
