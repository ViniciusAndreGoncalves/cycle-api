<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarketDataService
{
    protected $baseUrl = 'https://brapi.dev/api';
    protected $token;

    public function __construct()
    {
        $this->token = env('BRAPI_TOKEN', '');

        if (empty($this->token)) {
            Log::warning('MarketDataService: BRAPI_TOKEN está vazio ou não foi carregado!');
        }
    }
    /**
     * Busca cotações atuais de múltiplos ativos
     * Ex: ['PETR4', 'VALE3', 'BTC']
     */
    public function getPrices(array $tickers)
    {
        // Cache por 60 minutos para não ficar lento toda hora
        $cacheKey = 'prices_' . md5(implode('_', $tickers));

        return Cache::remember($cacheKey, 60 * 60, function () use ($tickers) {

            $prices = [];

            // --- MUDANÇA0: BUSCA ATIVO POR ATIVO ---
            foreach ($tickers as $ticker) {
                try {
                    // Log para acompanhar
                    //Log::info("Buscando individualmente: {$ticker}");

                    $response = Http::withOptions(['verify' => false])->get("{$this->baseUrl}/quote/{$ticker}", [
                        'token' => $this->token,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json()['results'][0] ?? null;
                        if ($data) {
                            $prices[$ticker] = $data['regularMarketPrice'];
                        }
                    } else {
                        // Se falhar um, loga e continua para o próximo
                        //Log::warning("Falha ao buscar {$ticker}: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("Erro conexão {$ticker}: " . $e->getMessage());
                }
            }
            return $prices;
        });
    }

    /**
     * Busca detalhes de um ativo para cadastro (Nome, Logo, etc)
     */
    public function searchAsset($ticker)
    {
        // Tenta buscar na API
        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(5)
                ->get("{$this->baseUrl}/quote/{$ticker}", [
                    'token' => $this->token,
                ]);
        } catch (\Exception $e) {
            Log::error("MarketDataService: Erro crítico de conexão: " . $e->getMessage());
            return null;
        }

        if ($response->failed()) {
            Log::error("MarketDataService: Brapi retornou erro. Status: " . $response->status());
            Log::error("MarketDataService: Body: " . $response->body());
            return null; // Retorna null para o Controller tratar
        }

        $results = $response->json()['results'] ?? [];

        if (empty($results)) {
            Log::info("MarketDataService: Brapi retornou 200 mas sem resultados para '{$ticker}'.");
            return null;
        }

        $data = $results[0];

        Log::info("MarketDataService: Sucesso. Nome encontrado: " . ($data['shortName'] ?? 'N/A'));

        return [
            'ticker' => $data['symbol'],
            'nome' => $data['shortName'] ?? $data['longName'] ?? $data['symbol'],
        ];
    }
}
