<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarteiraController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\AtivoController;
use App\Http\Controllers\CategoriaAtivoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DespesaController;
use App\Http\Controllers\AuthController;

use Illuminate\Support\Facades\Artisan;

Route::get('/diagnostico-controller', function () {
    $results = [];

    // 1. Verifica se o arquivo existe fisicamente no Linux
    $path = app_path('Http/Controllers/MovimentacaoController.php');
    $results['arquivo_existe'] = file_exists($path) ? 'SIM' : 'NÃO (Caminho: ' . $path . ')';

    // 2. Tenta carregar a classe manualmente para ver se explode erro
    try {
        if (class_exists(\App\Http\Controllers\MovimentacaoController::class)) {
            $results['classe_carregavel'] = 'SIM';
        } else {
            $results['classe_carregavel'] = 'NÃO (O arquivo existe, mas o PHP não consegue ler a classe inside)';
        }
    } catch (\Throwable $e) {
        $results['erro_fatal_ao_carregar'] = $e->getMessage();
    }

    // 3. Verifica dependências (O Suspeito Principal)
    try {
        if (class_exists(\App\Services\MarketDataService::class)) {
            $results['service_existe'] = 'SIM';
        } else {
            $results['service_existe'] = 'NÃO (MarketDataService não encontrado)';
        }
    } catch (\Throwable $e) {
        $results['erro_service'] = $e->getMessage();
    }

    return $results;
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rota pública para dados de mercado (Home)
Route::get('/market/highlights', [App\Http\Controllers\AtivoController::class, 'highlights']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('ativos', AtivoController::class);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::put('/user', [AuthController::class, 'update'])->name('user.update');
    Route::delete('/user', [AuthController::class, 'destroy'])->name('user.destroy');

    //Rotas para Carteira
    Route::get('/carteira/resumo', [CarteiraController::class, 'resumo']);
    Route::apiResource('carteira', CarteiraController::class);
    
    //Rotas para Movimentacao
    Route::apiResource('movimentacoes', MovimentacaoController::class);
    //Rotas com Resource Controllers
    Route::apiResource('despesas', DespesaController::class);
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('categorias-ativo', CategoriaAtivoController::class)->only(['index']);

    //Rota pra poder colocar um ativo na carteira de usuário
    Route::post('ativos', [AtivoController::class, 'store'])->name('ativos.store');
});
