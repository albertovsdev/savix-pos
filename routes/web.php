<?php

use App\Http\Controllers\AdministracionControlador;
use App\Http\Controllers\AutenticacionControlador;
use App\Http\Controllers\CatalogosControlador;
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

Route::middleware('auth')->prefix('catalogos')->group(function (): void {
    Route::get('/', [CatalogosControlador::class, 'mostrar'])->name('catalogos');
    Route::post('/categorias', [CatalogosControlador::class, 'crearCategoria'])->name('catalogos.categorias.crear');
    Route::put('/categorias/{categoria}/disponibilidad', [CatalogosControlador::class, 'actualizarDisponibilidadCategoria'])->name('catalogos.categorias.disponibilidad.actualizar');
    Route::post('/areas-preparacion', [CatalogosControlador::class, 'crearAreaPreparacion'])->name('catalogos.areas-preparacion.crear');
    Route::post('/insumos', [CatalogosControlador::class, 'crearInsumo'])->name('catalogos.insumos.crear');
    Route::post('/recetas', [CatalogosControlador::class, 'guardarReceta'])->name('catalogos.recetas.guardar');
    Route::delete('/recetas/{receta}', [CatalogosControlador::class, 'eliminarReceta'])->name('catalogos.recetas.eliminar');
    Route::post('/grupos-modificadores', [CatalogosControlador::class, 'crearGrupoModificador'])->name('catalogos.grupos-modificadores.crear');
    Route::post('/opciones-modificadores', [CatalogosControlador::class, 'crearOpcionModificador'])->name('catalogos.opciones-modificadores.crear');
    Route::delete('/opciones-modificadores/{opcion}', [CatalogosControlador::class, 'eliminarOpcionModificador'])->name('catalogos.opciones-modificadores.eliminar');
    Route::post('/productos', [CatalogosControlador::class, 'crearProducto'])->name('catalogos.productos.crear');
    Route::put('/productos/{producto}/disponibilidad', [CatalogosControlador::class, 'actualizarDisponibilidadProducto'])->name('catalogos.productos.disponibilidad.actualizar');
});
