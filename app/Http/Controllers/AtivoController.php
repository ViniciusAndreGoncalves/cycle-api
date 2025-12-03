<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ativo;
use App\Services\MarketDataService;

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
     * Retorna os destaques do mercado para a Home (Público)
     */
    public function highlights(MarketDataService $marketService)
    {
        $tickers = [
            'ITUB4',  // Itaú
            'PETR4',  // Petrobras
            'VALE3',  // Vale            
            'BPAC11', // BTG Pactual
            'ABEV3',  // Ambev
            'WEGE3',  // WEG            
            'ELET3',  // Eletrobras
            'BBAS3',  // Banco do Brasil
            ];
        
        // Busca na API
        $prices = $marketService->getPrices($tickers);
        
        $data = [];
        foreach ($tickers as $ticker) {
            
            // Inicializa com 0 por segurança
            $price = 0;

            // Se a API retornou este ticker, atualiza o valor
            if (isset($prices[$ticker])) {
                $price = $prices[$ticker];
            }
            // ---------------------------

            $data[] = [
                'symbol' => $ticker,
                'name' => $this->getAssetName($ticker), 
                'price' => (float)$price,
                'change' => 0, 
                'isCrypto' => false
            ];
        }

        return response()->json($data);
    }

    private function getAssetName($ticker) {
        return match ($ticker) {
            'PETR4' => 'Petrobras',
            'VALE3' => 'Vale',
            'ITUB4' => 'Itaú Unibanco',
            'WEGE3' => 'WEG',
            'MGLU3' => 'Magalu',
            'BBAS3' => 'Banco do Brasil',
            'ELET3', => 'Eletrobras',
            'ABEV3', => 'Ambev',
            'BPAC11', => 'BTG Pactual',
            default => $ticker
        };
    }
    
}
