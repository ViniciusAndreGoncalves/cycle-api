<?php

namespace Database\Seeders;

use App\Models\Ativo;
use App\Models\CategoriaAtivo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catAcoes = CategoriaAtivo::firstOrCreate(['nome' => 'Ações']);
        $catFII = CategoriaAtivo::firstOrCreate(['nome' => 'Fundos Imobiliários']);
        $catCripto = CategoriaAtivo::firstOrCreate(['nome' => 'Criptomoedas']);
        $catBDR = CategoriaAtivo::firstOrCreate(['nome' => 'BDRs']);
        $catRendaFixa = CategoriaAtivo::firstOrCreate(['nome' => 'Renda Fixa']);

        $acoes = [
            ['ticker' => 'PETR4', 'nome' => 'Petrobras PN'],
            ['ticker' => 'VALE3', 'nome' => 'Vale ON'],
            ['ticker' => 'ITUB4', 'nome' => 'Itaú Unibanco PN'],
            ['ticker' => 'BBAS3', 'nome' => 'Banco do Brasil ON'],
            ['ticker' => 'WEGE3', 'nome' => 'Weg ON'],
            ['ticker' => 'MGLU3', 'nome' => 'Magazine Luiza ON'],
        ];

        foreach ($acoes as $acao) {
            Ativo::firstOrCreate(
                ['ticker' => $acao['ticker']], 
                ['nome' => $acao['nome'], 'categoria_ativo_id' => $catAcoes->id]
            );
        }

        $fiis = [
            ['ticker' => 'MXRF11', 'nome' => 'Maxi Renda'],
            ['ticker' => 'HGLG11', 'nome' => 'CSHG Logística'],
            ['ticker' => 'KNRI11', 'nome' => 'Kinea Renda Imobiliária'],
            ['ticker' => 'XPML11', 'nome' => 'XP Malls'],
        ];

        foreach ($fiis as $fii) {
            Ativo::firstOrCreate(
                ['ticker' => $fii['ticker']], 
                ['nome' => $fii['nome'], 'categoria_ativo_id' => $catFII->id]
            );
        }

        $criptos = [
            ['ticker' => 'BTC', 'nome' => 'Bitcoin'],
            ['ticker' => 'ETH', 'nome' => 'Ethereum'],
            ['ticker' => 'SOL', 'nome' => 'Solana'],
            ['ticker' => 'USDT', 'nome' => 'Tether USD'],
            ['ticker' => 'LINK', 'nome' => 'Chainlink'],            
            ['ticker' => 'AVAX', 'nome' => 'Avalanche'],
            ['ticker' => 'DOT', 'nome' => 'Polkadot'],
            ['ticker' => 'UNI', 'nome' => 'Uniswap'],
        ];

        foreach ($criptos as $cripto) {
            Ativo::firstOrCreate(
                ['ticker' => $cripto['ticker']], 
                ['nome' => $cripto['nome'], 'categoria_ativo_id' => $catCripto->id]
            );
        }

    }
}
