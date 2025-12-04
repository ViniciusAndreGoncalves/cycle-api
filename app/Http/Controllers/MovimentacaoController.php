<?php

namespace App\Http\Controllers;

use App\Models\Ativo;
use App\Http\Controllers\Controller;
use App\Models\Movimentacao;
use App\Models\Carteira;
use App\Models\CategoriaAtivo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\MarketDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovimentacaoController extends Controller
{
    use AuthorizesRequests;
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

    public function store(Request $request, MarketDataService $marketService)
{
    // 1. Validação (Certifique-se que o Frontend envia estas chaves exatas)
    $validated = $request->validate([
        'ticker'            => 'required|string',
        'tipo'              => 'required|in:Compra,Venda',
        'quantidade'        => 'required|numeric|gt:0',
        'preco_unitario'    => 'required|numeric|gte:0',
        'data_movimentacao' => 'required|date',
    ]);

    $user = Auth::user();

    // Inicia uma transação para garantir integridade
    return DB::transaction(function () use ($validated, $user, $marketService) {

        // 2. Garante a Carteira
        $carteira = Carteira::firstOrCreate(
            ['user_id' => $user->id],
            ['nome' => 'Principal']
        );

        $tickerUpper = strtoupper($validated['ticker']);

        // 3. Busca ou Cria o Ativo
        $ativo = Ativo::where('ticker', $tickerUpper)->first();

        if (!$ativo) {
            // Busca dados na Brapi (API Externa)
            $dadosExternos = $marketService->searchAsset($tickerUpper);

            if (!$dadosExternos) {
                // Se não achar na API externa, aborta tudo (rollback automático da transaction)
                abort(404, 'Ativo não encontrado na Bolsa. Verifique o Ticker.');
            }

            // Categorização Automática
            $nomeCategoria = $this->detectarCategoria($tickerUpper);
            $categoria = CategoriaAtivo::firstOrCreate(['nome' => $nomeCategoria]);

            $ativo = Ativo::create([
                'ticker' => $dadosExternos['ticker'], // Garante o ticker oficial da API
                'nome'   => $dadosExternos['nome'] ?? $tickerUpper,
                'categoria_ativo_id' => $categoria->id,
                // Adicione 'logo' ou outros campos se houver
            ]);
        }

        // 4. O FINAL QUE FALTOU: Cria a Movimentação
        $movimentacao = $ativo->movimentacoes()->create([
            'carteira_id' => $carteira->id,
            'tipo'        => $validated['tipo'],
            'quantidade'  => $validated['quantidade'],
            'valor'       => $validated['preco_unitario'], // Mapeando para o nome da coluna no banco
            'data'        => $validated['data_movimentacao'],
            'user_id'     => $user->id // Se sua tabela tiver user_id direto
        ]);

        return response()->json([
            'message' => 'Movimentação realizada com sucesso!',
            'data' => $movimentacao
        ], 201);

    });
}

    // --- FUNÇÃO INTELIGENTE DE CATEGORIZAÇÃO ---
    private function detectarCategoria($ticker)
    {
        $ticker = strtoupper($ticker);

        // 1. Criptomoedas (Lista manual expandida)
        $criptos = ['BTC', 'ETH', 'SOL', 'USDT', 'BNB', 'XRP', 'ADA', 'DOGE', 'MATIC', 'LINK', 'LTC', 'DOT'];
        if (in_array($ticker, $criptos)) {
            return 'Criptomoedas';
        }

        // 2. BDRs (Final 32, 33, 34, 35)
        // Ex: NVDC34, AAPL34, MELI34
        if (preg_match('/(32|33|34|35)$/', $ticker)) {
            return 'BDRs'; // O nome deve bater com o getCorPorCategoria do CarteiraController
        }

        // 3. Fundos Imobiliários / ETFs / Units (Final 11)
        // Ex: MXRF11, HGLG11, IVVB11 (ETF)
        if (preg_match('/11$/', $ticker)) {
            // Nota: IVVB11 é ETF, mas aqui vai cair como FII. 
            // Para separar 100%, precisaríamos de uma lista manual ou API.
            // Para MVP, vamos considerar FIIs ou criar uma categoria mista.
            return 'Fundos Imobiliários (FIIs)';
        }

        // 4. Ações Nacionais (Final 3, 4, 5, 6)
        // Ex: PETR3, PETR4, VALE3
        if (preg_match('/[3456]$/', $ticker)) {
            return 'Ações'; // Nome exato do CarteiraController
        }

        // Se não souber, joga pro genérico
        return 'Outros Ativos';
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
            'tipo' => 'sometimes|in:Compra,Venda',
            'quantidade' => 'sometimes|numeric|gt:0',
            'preco_unitario' => 'sometimes|numeric|gte:0',
            'data_movimentacao' => 'sometimes|date',
        ]);


        $movimentacao->update($validated);
        return response()->json($movimentacao->load('ativo'), 200);
    }

    public function destroy(Movimentacao $movimentacao)
    {
        $this->authorize('delete', $movimentacao);

        $movimentacao->delete();

        return response()->noContent();
    }
}
