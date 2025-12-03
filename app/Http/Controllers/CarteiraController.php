<?php

namespace App\Http\Controllers;

use App\Models\Carteira;
use App\Services\MarketDataService;
use Illuminate\Support\Facades\DB;
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

    /**
     * RESUMO DA CARTEIRA
     * visão consolidada da carteira
     * Ela processa, soma, agrupa e calcula os totais.
     */
    public function resumo(MarketDataService $marketService)
    {
        $user = Auth::user();

        // 1. Garante carteira
        Carteira::firstOrCreate(['user_id' => $user->id], ['nome' => 'Principal']);

        // 2. Busca dados brutos
        $movimentacoes = DB::table('movimentacoes')
            ->join('ativos', 'movimentacoes.ativo_id', '=', 'ativos.id')
            ->join('categorias_ativos', 'ativos.categoria_ativo_id', '=', 'categorias_ativos.id')
            ->join('carteiras', 'movimentacoes.carteira_id', '=', 'carteiras.id')
            ->where('carteiras.user_id', $user->id)
            ->select(
                'ativos.ticker',
                'ativos.nome as nome_ativo',
                'categorias_ativos.nome as categoria',
                // Soma algébrica da quantidade (Compra +, Venda -)
                DB::raw("SUM(CASE WHEN movimentacoes.tipo = 'Compra' THEN movimentacoes.quantidade ELSE -movimentacoes.quantidade END) as total_qtd"),
                // Custo total (apenas compras contam para o preço médio ponderado neste MVP)
                DB::raw("SUM(CASE WHEN movimentacoes.tipo = 'Compra' THEN movimentacoes.quantidade * movimentacoes.preco_unitario ELSE 0 END) as custo_total_compras"),
                DB::raw("SUM(CASE WHEN movimentacoes.tipo = 'Compra' THEN movimentacoes.quantidade ELSE 0 END) as qtd_comprada")
            )
            ->groupBy('ativos.ticker', 'ativos.nome', 'categorias_ativos.nome')
            ->get();

        // 3. Busca Preços
        $tickers = $movimentacoes->pluck('ticker')->toArray();
        $precosAtuais = $marketService->getPrices($tickers);

        $resumoPorCategoria = [];
        $listaDetalhada = [];
        $totalGeral = 0;

        foreach ($movimentacoes as $item) {
            // Se a quantidade for 0 (vendeu tudo), pula
            if ($item->total_qtd <= 0) continue;

            // Preço Atual (Se a API falhar, usa o preço médio para não zerar o gráfico)
            $precoMedio = $item->qtd_comprada > 0 ? ($item->custo_total_compras / $item->qtd_comprada) : 0;
            $precoAtual = $precosAtuais[$item->ticker] ?? $precoMedio; 

            $saldoAtual = $item->total_qtd * $precoAtual;
            
            // Custo proporcional ao que sobrou na carteira
            $custoProporcional = $item->total_qtd * $precoMedio;

            $rentabilidadeValor = $saldoAtual - $custoProporcional;
            
            $rentabilidadePerc = $custoProporcional > 0 
                ? (($saldoAtual - $custoProporcional) / $custoProporcional) * 100 
                : 0;

            $listaDetalhada[] = [
                'ticker' => $item->ticker,
                'nome' => $item->nome_ativo,
                'categoria' => $item->categoria,
                'qtd' => (float) $item->total_qtd,
                'preco_medio' => (float) $precoMedio,
                'preco_atual' => (float) $precoAtual,
                'saldo_atual' => (float) $saldoAtual,
                'rentabilidade_perc' => round($rentabilidadePerc, 2),
                'rentabilidade_valor' => round($rentabilidadeValor, 2),
                'seta' => $rentabilidadePerc >= 0 ? 'up' : 'down'
            ];

            if (!isset($resumoPorCategoria[$item->categoria])) {
                $resumoPorCategoria[$item->categoria] = 0;
            }
            $resumoPorCategoria[$item->categoria] += $saldoAtual;
            $totalGeral += $saldoAtual;
        }

        $grafico = [];
        foreach ($resumoPorCategoria as $categoria => $valor) {
            $grafico[] = [
                'name' => $categoria,
                'value' => (float) $valor,
                'percentage' => $totalGeral > 0 ? round(($valor / $totalGeral) * 100, 2) : 0,
                'fill' => $this->getCorPorCategoria($categoria)
            ];
        }

        return response()->json([
            'total_patrimonio' => $totalGeral,
            'grafico' => $grafico,
            'detalhes' => $listaDetalhada
        ]);
    }

    // Auxiliar para cores
    private function getCorPorCategoria($nome)
    {
        // Normaliza o nome para evitar erros bobos (opcional, mas ajuda)
        // Mas vamos focar no match direto para ser mais rápido
        
        return match ($nome) {
            // VERDE (Ações)
            'Ações', 
            'Ações Nacionais', 
            'Acao', 
            'Stocks' 
            => '#10b981', 

            // ROXO (Cripto)
            'Criptomoedas', 
            'Cripto', 
            'Bitcoin', 
            'Altcoins' 
            => '#8b5cf6',

            // LARANJA (FIIs)
            'Fundos Imobiliários', 
            'Fundos Imobiliários (FIIs)', 
            'FIIs', 
            'FII' 
            => '#f59e0b',

            // AZUL (Renda Fixa)
            'Renda Fixa', 
            'Tesouro Direto', 
            'CDB', 
            'LCI/LCA' 
            => '#3b82f6',

            // VERMELHO (Internacional / BDRs)
            'BDRs', 
            'BDR', 
            'Stocks (EUA)', 
            'ETF' 
            => '#ef4444',

            // ROSA (Categoria Padrão do Cadastro Automático)
            'Novos Ativos', 
            'Outros' 
            => '#ec4899',

            // CINZA (Padrão se não achar nada)
            default => '#71717a', 
        };
    }
}
