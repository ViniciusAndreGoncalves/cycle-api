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
        $response = Http::get("{$this->baseUrl}/quote/{$ticker}", [
            'token' => $this->token,
        ]);

        if ($response->failed() || empty($response->json()['results'])) {
            return null; // Não existe na Brapi
        }

        $data = $response->json()['results'][0];

        return [
            'ticker' => $data['symbol'],
            'nome' => $data['shortName'] ?? $data['longName'] ?? $data['symbol'],
            // A Brapi retorna o tipo? Às vezes sim, às vezes não. 
            // Por padrão, se não souber, jogar na categoria "Ações" (ID 1) ou criar uma lógica extra.
            // Para o MVP, assume-se que se achou, é válido.
        ];
    }
}