<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MarketDataService
{
    protected $baseUrl = 'https://brapi.dev/api';
    protected $token;
    // Você pode pegar um token grátis no site deles, ou usar sem token (limitado)
    public function __construct()
    {
        $this->token = env('BRAPI_TOKEN', '');
    }
    /**
     * Busca cotações atuais de múltiplos ativos
     * Ex: ['PETR4', 'VALE3', 'BTC']
     */
    public function getPrices(array $tickers)
    {

        // Ordena para garantir que ['PETR4', 'VALE3'] gere a mesma chave que ['VALE3', 'PETR4']
        sort($tickers);
        $cacheKey = 'prices_' . md5(implode('_', $tickers));
        /**
        * Cache::remember faz a mágica:
        * 1. Procura 'prices_xyz' no cache.
        * 2. Se achar, devolve na hora (sem gastar API).
        * 3. Se não achar, roda a função, vai na API, SALVA no cache por 15 min e devolve.
         */
        $cacheKey = 'prices_' . implode('_', $tickers);
        
        return Cache::remember($cacheKey, 60 * 15, function () use ($tickers) {
            
            // Se não tá no cache, vai na API
            $tickersString = implode(',', $tickers);
            
            // A API da Brapi aceita múltiplos tickers separados por vírgula
            $response = Http::get("{$this->baseUrl}/quote/{$tickersString}", [
                'token' => $this->token,
            ]);

            if ($response->failed()) {
                return [];
            }

            // Formata a resposta para um array simples: ['PETR4' => 35.50, 'VALE3' => 60.00]
            $data = $response->json()['results'] ?? [];
            
            $prices = [];
            foreach ($data as $item) {
                $prices[$item['symbol']] = $item['regularMarketPrice'];
            }

            return $prices;
        });
    }
}