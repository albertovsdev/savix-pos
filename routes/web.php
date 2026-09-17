<?php

use App\Http\Controllers\AutenticacionControlador;
use App\Http\Controllers\ConfiguracionInicialControlador;
use App\Http\Controllers\PanelControlador;
use App\Models\Negocio;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Negocio::query()->exists()
        ? redirect()->route('panel')
        : redirect()->route('configuracion-inicial');
});

Route::get('/configuracion-inicial', [ConfiguracionInicialControlador::class, 'crear'])->name('configuracion-inicial');
Route::post('/configuracion-inicial', [ConfiguracionInicialControlador::class, 'guardar'])->middleware('throttle:6,1');

Route::get('/acceso', [AutenticacionControlador::class, 'crear'])->middleware('guest')->name('login');
Route::post('/acceso', [AutenticacionControlador::class, 'iniciar'])->middleware(['guest', 'throttle:6,1']);
Route::delete('/acceso', [AutenticacionControlador::class, 'destruir'])->middleware('auth')->name('logout');

Route::get('/panel', [PanelControlador::class, 'mostrar'])->middleware('auth')->name('panel');
