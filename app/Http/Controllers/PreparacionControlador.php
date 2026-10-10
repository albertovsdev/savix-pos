<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\Usuario;
use App\Services\ServicioPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PreparacionControlador extends Controller
{
    public function __construct(private readonly ServicioPermisos $servicio_permisos)
    {
    }

    public function mostrar(Request $solicitud): Response
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'preparacion.ver', $sucursal->id_sucursal);

        $areas = $this->areasPermitidas($solicitud->user(), $sucursal->id_sucursal);
        $id_area = $solicitud->integer('ref_area_preparacion') ?: $areas->first()?->id_area_preparacion;
        if ($solicitud->filled('ref_area_preparacion')) {
            abort_unless($areas->contains('id_area_preparacion', $id_area), 404);
        }
        $area = $id_area ? $areas->firstWhere('id_area_preparacion', $id_area) : null;

        return Inertia::render('Preparacion', [
            'sucursal_seleccionada' => $sucursal->only(['id_sucursal', 'nombre', 'clave']),
            'sucursales' => $this->sucursalesUsuario($solicitud),
            'areas' => $areas,
            'area_seleccionada' => $area,
            'detalles' => $area ? $this->detallesPendientes($area->id_area_preparacion) : [],
            'puede_actualizar' => $this->servicio_permisos->tiene($solicitud->user(), 'preparacion.actualizar_estado', $sucursal->id_sucursal),
        ]);
    }

    public function actualizarEstado(Request $solicitud, int $id_detalle_area): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'preparacion.actualizar_estado', $sucursal->id_sucursal);

        $datos = $solicitud->validate([
            'estado_preparacion' => ['required', Rule::in(['en_preparacion', 'listo', 'entregado'])],
        ]);

        $destino = DB::table('detalles_rondas_pedidos_areas_preparacion')
            ->join('detalles_rondas_pedidos', 'detalles_rondas_pedidos.id_detalle_ronda_pedido', '=', 'detalles_rondas_pedidos_areas_preparacion.ref_detalle_ronda_pedido')
            ->join('rondas_pedidos', 'rondas_pedidos.id_ronda_pedido', '=', 'detalles_rondas_pedidos.ref_ronda_pedido')
            ->join('pedidos', 'pedidos.id_pedido', '=', 'rondas_pedidos.ref_pedido')
            ->where('detalles_rondas_pedidos_areas_preparacion.id_detalle_ronda_pedido_area_preparacion', $id_detalle_area)
            ->where('pedidos.ref_sucursal', $sucursal->id_sucursal)
            ->where('detalles_rondas_pedidos_areas_preparacion.activo', true)
            ->select([
                'detalles_rondas_pedidos_areas_preparacion.id_detalle_ronda_pedido_area_preparacion',
                'detalles_rondas_pedidos_areas_preparacion.ref_area_preparacion',
                'detalles_rondas_pedidos_areas_preparacion.estado_preparacion',
            ])
            ->first();
        abort_unless($destino, 404);
        abort_unless($this->areasPermitidas($solicitud->user(), $sucursal->id_sucursal)->contains('id_area_preparacion', $destino->ref_area_preparacion), 403);

        $siguientes = [
            'pendiente' => 'en_preparacion',
            'en_preparacion' => 'listo',
            'listo' => 'entregado',
        ];
        abort_unless(($siguientes[$destino->estado_preparacion] ?? null) === $datos['estado_preparacion'], 422);

        DB::table('detalles_rondas_pedidos_areas_preparacion')
            ->where('id_detalle_ronda_pedido_area_preparacion', $destino->id_detalle_ronda_pedido_area_preparacion)
            ->update(['estado_preparacion' => $datos['estado_preparacion'], 'actualizado_en' => now()]);

        $this->registrar(
            $solicitud,
            'actualizar_estado_preparacion',
            'detalles_rondas_pedidos_areas_preparacion',
            $destino->id_detalle_ronda_pedido_area_preparacion,
            ['estado_preparacion' => $datos['estado_preparacion']],
            $sucursal->id_sucursal,
        );

        return back()->with('exito', 'El estado de preparación fue actualizado.');
    }

    private function detallesPendientes(int $id_area): Collection
    {
        $detalles = DB::table('detalles_rondas_pedidos_areas_preparacion')
            ->join('detalles_rondas_pedidos', 'detalles_rondas_pedidos.id_detalle_ronda_pedido', '=', 'detalles_rondas_pedidos_areas_preparacion.ref_detalle_ronda_pedido')
            ->join('rondas_pedidos', 'rondas_pedidos.id_ronda_pedido', '=', 'detalles_rondas_pedidos.ref_ronda_pedido')
            ->join('pedidos', 'pedidos.id_pedido', '=', 'rondas_pedidos.ref_pedido')
            ->leftJoin('mesas', 'mesas.id_mesa', '=', 'pedidos.ref_mesa')
            ->where('detalles_rondas_pedidos_areas_preparacion.ref_area_preparacion', $id_area)
            ->where('detalles_rondas_pedidos_areas_preparacion.activo', true)
            ->where('detalles_rondas_pedidos.estado', 'activo')
            ->where('detalles_rondas_pedidos.activo', true)
            ->where('rondas_pedidos.estado', 'enviada')
            ->where('pedidos.estado', 'abierto')
            ->whereNotIn('detalles_rondas_pedidos_areas_preparacion.estado_preparacion', ['entregado', 'cancelado'])
            ->orderBy('rondas_pedidos.enviada_en')
            ->orderBy('detalles_rondas_pedidos_areas_preparacion.id_detalle_ronda_pedido_area_preparacion')
            ->get([
                'detalles_rondas_pedidos_areas_preparacion.id_detalle_ronda_pedido_area_preparacion',
                'detalles_rondas_pedidos_areas_preparacion.estado_preparacion',
                'detalles_rondas_pedidos.id_detalle_ronda_pedido',
                'detalles_rondas_pedidos.cantidad',
                'detalles_rondas_pedidos.nombre_producto',
                'detalles_rondas_pedidos.nota_preparacion',
                'rondas_pedidos.numero_ronda',
                'rondas_pedidos.enviada_en',
                'pedidos.tipo_servicio',
                'pedidos.referencia_mostrador',
                'mesas.nombre as mesa',
            ]);
        $modificadores = DB::table('detalles_rondas_pedidos_modificadores')
            ->whereIn('ref_detalle_ronda_pedido', $detalles->pluck('id_detalle_ronda_pedido'))
            ->where('activo', true)
            ->get(['ref_detalle_ronda_pedido', 'nombre_opcion']);

        return $detalles->map(fn (object $detalle) => [
            ...get_object_vars($detalle),
            'modificadores' => $modificadores
                ->where('ref_detalle_ronda_pedido', $detalle->id_detalle_ronda_pedido)
                ->pluck('nombre_opcion')
                ->values(),
        ]);
    }

    private function areasPermitidas(Usuario $usuario, int $id_sucursal): Collection
    {
        $consulta = DB::table('areas_preparacion')
            ->where('ref_sucursal', $id_sucursal)
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre');

        if (! $this->esAdministrador($usuario, $id_sucursal)) {
            $consulta->whereIn('id_area_preparacion', DB::table('usuarios_areas_preparacion')
                ->where('ref_usuario', $usuario->id_usuario)
                ->where('activo', true)
                ->pluck('ref_area_preparacion'));
        }

        return $consulta->get(['id_area_preparacion', 'nombre', 'codigo']);
    }

    private function esAdministrador(Usuario $usuario, int $id_sucursal): bool
    {
        return DB::table('usuarios_roles')
            ->join('cat_roles', 'cat_roles.id_rol', '=', 'usuarios_roles.ref_rol')
            ->where('usuarios_roles.ref_usuario', $usuario->id_usuario)
            ->where('usuarios_roles.activo', true)
            ->whereIn('cat_roles.codigo', ['dev', 'admin'])
            ->where(fn ($consulta) => $consulta->whereNull('usuarios_roles.ref_sucursal')->orWhere('usuarios_roles.ref_sucursal', $id_sucursal))
            ->exists();
    }

    private function sucursalesUsuario(Request $solicitud): Collection
    {
        return Sucursal::query()
            ->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)
            ->where('usuarios_sucursales.activo', true)
            ->where('sucursales.activo', true)
            ->orderByDesc('usuarios_sucursales.es_principal')
            ->orderBy('sucursales.nombre')
            ->get(['sucursales.id_sucursal', 'sucursales.nombre', 'sucursales.clave']);
    }

    private function sucursalSeleccionada(Request $solicitud): Sucursal
    {
        $id_sucursal = $solicitud->integer('ref_sucursal');

        return Sucursal::query()
            ->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)
            ->where('usuarios_sucursales.activo', true)
            ->where('sucursales.activo', true)
            ->when($id_sucursal, fn ($consulta) => $consulta->where('sucursales.id_sucursal', $id_sucursal))
            ->orderByDesc('usuarios_sucursales.es_principal')
            ->select('sucursales.*')
            ->firstOrFail();
    }

    private function autorizar(Request $solicitud, string $permiso, ?int $id_sucursal = null): void
    {
        abort_unless($this->servicio_permisos->tiene($solicitud->user(), $permiso, $id_sucursal), 403);
    }

    private function registrar(Request $solicitud, string $accion, string $entidad, int $id_entidad, array $datos_nuevos, ?int $id_sucursal): void
    {
        DB::table('bitacora_auditoria')->insert([
            'ref_usuario' => $solicitud->user()->id_usuario,
            'ref_sucursal' => $id_sucursal,
            'modulo' => 'preparacion',
            'accion' => $accion,
            'entidad' => $entidad,
            'ref_entidad' => $id_entidad,
            'datos_nuevos' => json_encode($datos_nuevos),
            'direccion_ip' => $solicitud->ip(),
            'agente_usuario' => $solicitud->userAgent(),
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
    }
}
