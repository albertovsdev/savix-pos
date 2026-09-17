<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use App\Services\ServicioPermisos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PanelControlador extends Controller
{
    public function __construct(private readonly ServicioPermisos $servicio_permisos)
    {
    }

    public function mostrar(Request $solicitud): Response
    {
        $usuario = $solicitud->user();

        $sucursal = DB::table('usuarios_sucursales')
            ->join('sucursales', 'sucursales.id_sucursal', '=', 'usuarios_sucursales.ref_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $usuario->id_usuario)
            ->where('usuarios_sucursales.activo', true)
            ->orderByDesc('usuarios_sucursales.es_principal')
            ->select('sucursales.*')
            ->first();

        $negocio = Negocio::query()->first();

        $modulos = $sucursal
            ? DB::table('sucursales_modulos')
                ->join('cat_modulos', 'cat_modulos.id_modulo', '=', 'sucursales_modulos.ref_modulo')
                ->where('sucursales_modulos.ref_sucursal', $sucursal->id_sucursal)
                ->where('sucursales_modulos.habilitado', true)
                ->orderBy('cat_modulos.orden')
                ->pluck('cat_modulos.nombre')
                ->all()
            : [];

        $puede_administrar = $this->servicio_permisos->tiene($usuario, 'negocio.ver')
            || $this->servicio_permisos->tiene($usuario, 'usuarios.ver')
            || $this->servicio_permisos->tiene($usuario, 'sucursales.ver');
        $puede_catalogos = $this->servicio_permisos->tiene($usuario, 'catalogos.ver');

        return Inertia::render('Panel', [
            'usuario' => ['nombre' => $usuario->nombre, 'nombre_usuario' => $usuario->nombre_usuario],
            'negocio' => $negocio ? ['nombre_comercial' => $negocio->nombre_comercial] : null,
            'sucursal' => $sucursal,
            'modulos' => $modulos,
            'puede_administrar' => $puede_administrar,
            'puede_catalogos' => $puede_catalogos,
        ]);
    }
}
