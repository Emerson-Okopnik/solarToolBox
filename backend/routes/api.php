<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FabricanteController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\InversorController;
use App\Http\Controllers\ClimaController;
use App\Http\Controllers\ProjetoController;
use App\Http\Controllers\ArranjoController;
use App\Http\Controllers\StringController;
use App\Http\Controllers\ExecucaoController;

// Autenticação
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rotas públicas (catálogos)
Route::prefix('catalogos')->group(function () {
    Route::get('/fabricantes', [FabricanteController::class, 'index']);
    Route::get('/modulos', [ModuloController::class, 'index']);
    Route::get('/inversores', [InversorController::class, 'index']);
    Route::get('/inversores/{inversor}/mppts', [InversorController::class, 'getMppts']);
    Route::get('/climas', [ClimaController::class, 'index']);
});

Route::middleware('jwt.auth')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Gestão de catálogos (admin)
    Route::apiResource('fabricantes', FabricanteController::class)->except(['index']);
    Route::apiResource('modulos', ModuloController::class)->except(['index']);
    Route::apiResource('inversores', InversorController::class)->except(['index']);
    Route::apiResource('climas', ClimaController::class)->except(['index']);

    // Projetos
    Route::apiResource('projetos', ProjetoController::class);
    Route::post('/projetos/{projeto:id}/arranjos', [ArranjoController::class, 'store']);
    Route::get('/projetos/{projeto:id}/arranjos', [ArranjoController::class, 'index']);
    Route::apiResource('arranjos', ArranjoController::class)->except(['store', 'index']);

    // Strings
    Route::post('/arranjos/{arranjo:id}/strings', [StringController::class, 'store']);
    Route::get('/arranjos/{arranjo:id}/strings', [StringController::class, 'index']);
    Route::apiResource('strings', StringController::class)->except(['store', 'index']);

    // Execução e cálculos
    Route::post('/projetos/{projeto:id}/executar', [ExecucaoController::class, 'executar']);
    Route::get('/execucoes/{execucao:id}', [ExecucaoController::class, 'show']);
    Route::get('/projetos/{projeto:id}/execucoes', [ExecucaoController::class, 'index']);
});
