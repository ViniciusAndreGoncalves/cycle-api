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
use Illuminate\Support\Facades\Log;

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
        $movimentacoes = Movimentacao::whereHas('carteira', function ($query) {
            $query->where('user_id', Auth::id());
        })->with(['ativo', 'carteira'])
            ->latest('data_movimentacao')
            ->get();

        return $movimentacoes;
    }

    public function store(Request $request, MarketDataService $marketService)
    {
        Log::info('--- INÍCIO NOVA TRANSAÇÃO ---');

        // 1. Validação
        $validated = $request->validate([
            'ticker'            => 'required|string',
            'tipo'              => 'required|in:Compra,Venda',
            'quantidade'        => 'required|numeric|gt:0',
            'preco_unitario'    => 'required|numeric|gte:0',
            'data_movimentacao' => 'required|date',
        ]);

        Log::info('Dados validados:', $validated);

        $user = Auth::user();

        return DB::transaction(function () use ($validated, $user, $marketService) {

            // Garante a Carteira
            $carteira = Carteira::firstOrCreate(
                ['user_id' => $user->id],
                ['nome' => 'Principal']
            );

            // Tratamento do Ticker
            $tickerRaw = strtoupper($validated['ticker']); // O que o usuário digitou (ex: GARE11)

            // Tenta buscar no banco local
            $ativo = Ativo::where('ticker', $tickerRaw)->first();

            // Se não achou, tenta buscar com .SA (caso tenha sido salvo correto antes)
            if (!$ativo) {
                $ativo = Ativo::where('ticker', $tickerRaw . '.SA')->first();
            }

            if (!$ativo) {
                Log::info("Ativo {$tickerRaw} não existe no BD local. Buscando fora...");

                // TENTATIVA 1: Com sufixo .SA (Padrão Brasil)
                $tickerBusca = str_ends_with($tickerRaw, '.SA') ? $tickerRaw : $tickerRaw . '.SA';
                Log::info("Tentando API externa com: {$tickerBusca}");

                $dadosExternos = $marketService->searchAsset($tickerBusca);

                // TENTATIVA 2: Sem sufixo (Caso seja cripto ou exceção)
                if (!$dadosExternos) {
                    Log::info("Falha com .SA. Tentando API externa puro: {$tickerRaw}");
                    $dadosExternos = $marketService->searchAsset($tickerRaw);
                }

                if (!$dadosExternos) {
                    Log::error("FALHA TOTAL: Ativo não encontrado em nenhuma variação.");

                    // Retorna JSON para o front entender o erro
                    return response()->json([
                        'message' => "Não encontramos o ativo '{$tickerRaw}'. Tente adicionar '.SA' no final ou verifique o código."
                    ], 404);
                }

                Log::info("Sucesso na API externa. Dados recebidos:", $dadosExternos);

                // Categorização
                $nomeCategoria = $this->detectarCategoria($tickerRaw);
                $categoria = CategoriaAtivo::firstOrCreate(['nome' => $nomeCategoria]);

                $ativo = Ativo::create([
                    'ticker' => $dadosExternos['ticker'], // Salva como veio da API (provavelmente GARE11.SA)
                    'nome'   => $dadosExternos['nome'] ?? $tickerRaw,
                    'categoria_ativo_id' => $categoria->id,
                ]);
            }

            // Cria a movimentação
            $movimentacao = $ativo->movimentacoes()->create([
                'carteira_id' => $carteira->id,
                'tipo'        => $validated['tipo'],
                'quantidade'  => $validated['quantidade'],
                'preco_unitario'       => $validated['preco_unitario'],
                'data_movimentacao'        => $validated['data_movimentacao'],                
            ]);

            Log::info('--- TRANSAÇÃO SALVA COM SUCESSO ---');

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
