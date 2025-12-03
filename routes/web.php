<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/limpar-tudo', function () {
    // comando para limpar cache internamente -> Deploy
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    
    return "Cache limpo com sucesso! Configurações atualizadas.";
});

Route::get('/rotas-debug', function () {
    $routes = Route::getRoutes();
    $html = "<h1>Lista de Rotas Registradas</h1><ul>";
    
    foreach ($routes as $route) {
        $html .= "<li><strong>" . $route->methods()[0] . "</strong>: " . $route->uri() . "</li>";
    }
    
    return $html . "</ul>";
});