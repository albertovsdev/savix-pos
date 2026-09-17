<?php

use App\Http\Controllers\AdministracionControlador;
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

Route::middleware('auth')->prefix('administracion')->group(function (): void {
    Route::get('/', [AdministracionControlador::class, 'mostrar'])->name('administracion');
    Route::put('/negocio', [AdministracionControlador::class, 'actualizarNegocio'])->name('administracion.negocio.actualizar');
    Route::post('/sucursales', [AdministracionControlador::class, 'crearSucursal'])->name('administracion.sucursales.crear');
    Route::put('/sucursales/{sucursal}', [AdministracionControlador::class, 'actualizarSucursal'])->name('administracion.sucursales.actualizar');
    Route::post('/usuarios', [AdministracionControlador::class, 'crearUsuario'])->name('administracion.usuarios.crear');
    Route::put('/usuarios/{usuario}/permisos', [AdministracionControlador::class, 'guardarPermisoUsuario'])->name('administracion.usuarios.permisos.guardar');
});
