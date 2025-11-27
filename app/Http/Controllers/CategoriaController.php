<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriaController extends Controller
{
    /**
     * LISTAR (GET /api/categorias)
     * Retorna por exemplo, "Alimentação", "Lazer" etc., do usuário logado.
     */
    public function index()
    {
        return Auth::user()->categorias;
    }

    /**
     * CRIAR (POST /api/categorias)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:45',
            'cor' => 'nullable|string|max:7',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $categoria = $user->categorias()->create($validated);

        return response()->json($categoria, 201);
    }

    /**
     * MOSTRAR (GET /api/categorias/{id})
     */
    public function show(Categoria $categoria)
    {
        if ($categoria->user_id !== Auth::id()) {
            return response()->json(['messege' => 'Acesso negado'], 403);
        }
        return $categoria;
    }

    /**
     * ATUALIZAR (PUT/PATCH /api/categorias/{id})
     */
    public function update(Request $request, Categoria $categoria)
    {
        if ($categoria->user_id !== Auth::id()) {
            return response()->json(['messege' => 'Acesso negado'], 403);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:45',
            'cor' => 'nullable|string|max:7',
        ]);

        $categoria->update($validated);
        return $categoria;
    }

    /**
     * DELETAR (DELETE /api/categorias/{id})
     */
    public function destroy(Categoria $categoria)
    {
        if ($categoria->user_id !== Auth::id()) {
            return response()->json(['messege' => 'Acesso negado'], 403);
        }

        $categoria->delete();
        return response()->noContent();
    }
}
