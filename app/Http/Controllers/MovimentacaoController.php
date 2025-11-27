<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovimentacaoRequest;
use App\Models\Movimentacao;
use App\Models\Carteira;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovimentacaoController extends Controller
{
    /**
     * LISTAR (GET /api/movimentacoes)
     * Retorna o histórico de operações.
     */
    public function index()
    {
        // Busca todas as movimentações onde a carteira pertence ao usuário logado
        // Usamos o 'whereHas' para filtrar pelo relacionamento
        $movimentacoes = Movimentacao::whereHas('carteira', function ($query){
            $query->where('user_id', Auth::id());
        })->with(['ativo', 'carteira'])
        ->latest('data_movimentacao')
        ->get();

        return $movimentacoes;
    }

    public function store(StoreMovimentacaoRequest $request)
    {
        $carteira = Carteira::find($request['carteira_id']);

        if ($carteira->user_id !== Auth::id()) {
            return response()->json(['error' => 'Acesso negado à carteira especificada.'], 403);
        }

        $movimentacao = Movimentacao::create($request->validated());

        return response()->json($movimentacao->load('ativo'), 201);

    }

    /**
     * DETALHAR (GET /api/movimentacoes/{id})
     */
    public function show(Movimentacao $movimentacao)
    {
        if ($movimentacao->user_id !== Auth::id()) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        return $movimentacao->load(['ativo', 'carteira']);
    }

    /**
     * ATUALIZAR (PUT/PATCH /api/movimentacoes/{id})
     */
    public function update(Movimentacao $movimentacao)
    {
        if ($movimentacao->carteira->user_id !== Auth::id()) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $validated = request()->validate([
            'carteira_id' => 'sometimes|exists:carteiras,id',
            'ativo_id' => 'sometimes|exists:ativos,id',
            'tipo' => 'sometimes|in:Compra,Venda,Aporte,Resgate',
            'quantidade' => 'sometimes|numeric|gt:0',
            'preco_unitario' => 'sometimes|numeric|gte:0',
            'data_movimentacao' => 'sometimes|date',
        ]);


        return $movimentacao->load(['ativo', 'carteira'], 200);
    }

    /**
     * DELETAR (DELETE /api/movimentacoes/{id})
     */
    public function destroy(Movimentacao $movimentacao)
    {
        if ($movimentacao->carteira->user_id !== Auth::id()) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $movimentacao->delete();

        return response()->noContent();
    }
}
