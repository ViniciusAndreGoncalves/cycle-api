<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CarteiraController;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\AtivoController;
use App\Http\Controllers\CategoriaAtivoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DespesaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    //Rotas para Carteira
    Route::get('/carteira', [CarteiraController::class, 'index'])->name('carteira.index');
    Route::post('/carteira', [CarteiraController::class, 'store'])->name('carteira.store');
    Route::get('/carteira/{carteira}', [CarteiraController::class, 'show'])->name('carteira.show');
    Route::put('/carteira/{carteira}', [CarteiraController::class, 'update'])->name('carteira.update');
    Route::delete('/carteira/{carteira}', [CarteiraController::class, 'destroy'])->name('carteira.delete');
    
    //Rotas para Movimentacao
    Route::get('/movimentacoes', [MovimentacaoController::class, 'index'])->name('movimentacoes.index');
    Route::post('/movimentacoes', [MovimentacaoController::class, 'store'])->name('movimentacoes.store');
    Route::get('/movimentacoes/{movimentacao}', [MovimentacaoController::class, 'show'])->name('movimentacoes.show');
    Route::patch('/movimentacoes/{movimentacao}', [MovimentacaoController::class, 'update'])->name('movimentacoes.update');
    Route::delete('/movimentacoes/{movimentacao}', [MovimentacaoController::class, 'destroy'])->name('movimentacoes.delete');

    //Rotas para Ativo
    Route::apiResource('ativos', AtivoController::class)->only(['index', 'store', 'show']);

    Route::apiResource('despesas', DespesaController::class);
    Route::apiResource('ativo', AtivoController::class);
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('categorias-ativo', CategoriaAtivoController::class)->only(['index']);
});
