<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movimentacao;
use App\Models\Ativo;
use App\Models\User;
use App\Models\Carteira;
use Carbon\Carbon;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->error("O usuário com ID 3 não foi encontrado!");
            return;
        }

        $this->command->info("Semeando dados para: " . $user->name);

        // Garantir que esse usuário tenha uma Carteira
        // Se não tiver, cria uma agora.
        $carteira = Carteira::firstOrCreate(
            ['user_id' => $user->id],
            ['nome' => 'Minha Carteira Principal']
        );

        // Busca IDs dos ativos (que já existem do AssetSeeder)
        $petr4 = Ativo::where('ticker', 'PETR4')->first();
        $btc = Ativo::where('ticker', 'BTC')->first();
        $mxrf11 = Ativo::where('ticker', 'MXRF11')->first();

        //  Compra de Ações (PETR4)
        if ($petr4) {
            Movimentacao::create([
                'carteira_id' => $carteira->id, // Usa o ID da carteira do User 3                
                'ativo_id' => $petr4->id,
                'tipo' => 'compra',
                'quantidade' => 100,
                'preco_unitario' => 32.50,
                'data_movimentacao' => Carbon::now()->subMonths(2),
            ]);
        }

        //  Compra de Cripto (BTC)
        if ($btc) {
            Movimentacao::create([
                'carteira_id' => $carteira->id,                
                'ativo_id' => $btc->id,
                'tipo' => 'compra',
                'quantidade' => 0.005,
                'preco_unitario' => 350000.00,
                'data_movimentacao' => Carbon::now()->subMonths(1),
            ]);
        }

        //  Compra de FII (MXRF11)
        if ($mxrf11) {
            Movimentacao::create([
                'carteira_id' => $carteira->id,                
                'ativo_id' => $mxrf11->id,
                'tipo' => 'compra',
                'quantidade' => 50,
                'preco_unitario' => 10.10,
                'data_movimentacao' => Carbon::now()->subDays(15),
            ]);
        }
    }
}
