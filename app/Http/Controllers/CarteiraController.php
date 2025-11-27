<?php

namespace App\Http\Controllers;

use App\Models\Carteira;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarteiraController extends Controller
{
    /**
     * LISTAR (GET /api/carteiras)
     * Retorna todas as carteiras do usuário logado.
     */
    public function index()
    {
        // Pega o usuário logado e retorna suas carteiras
        // O Laravel transforma isso em JSON automaticamente
        return Auth::user()->carteiras;
    }

    /**
     * CRIAR (POST /api/carteiras)
     * Cria uma nova carteira no banco.
     */
    public function store(Request $request)
    {
        // 1. Validação: Garante que o nome foi enviado e não é muito longo
        $validated = $request->validate([
            'nome' => 'required|string|max:40',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        // 2. Criação: Usa o relacionamento para criar já vinculando ao usuário
        // Isso é mais seguro do que passar 'user_id' manualmente
        $carteira = $user->carteiras()->create($validated);

        // 3. Retorno: Devolve a carteira criada e status 201 (Created)
        return response()->json($carteira, 201);
    }

    /**
     * DETALHAR (GET /api/carteiras/{id})
     * Mostra uma carteira específica.
     */
    public function show(Carteira $carteira)
    {
        // Verificação de segurança: O usuário é dono dessa carteira?
        if ($carteira->user_id !== Auth::id()) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        return $carteira;
    }

    /**
     * ATUALIZAR (PUT/PATCH /api/carteiras/{id})
     * Atualiza o nome da carteira.
     */
    public function update(Request $request, Carteira $carteira)
    {
        // Segurança
        if ($carteira->user_id !== Auth::id()) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        // Validação
        $validated = $request->validate([
            'nome' => 'required|string|max:40',
        ]);

        // Atualização
        $carteira->update($validated);

        return $carteira;
    }

    /**
     * DELETAR (DELETE /api/carteiras/{id})
     * Remove a carteira (e as movimentações em cascata).
     */
    public function destroy(Carteira $carteira)
    {
        // Segurança
        if ($carteira->user_id !== Auth::id()) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $carteira->delete();

        // 204 No Content = Deletado com sucesso, sem corpo de resposta
        return response()->noContent();
    }
}