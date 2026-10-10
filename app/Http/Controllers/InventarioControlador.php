<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Services\ServicioPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InventarioControlador extends Controller
{
    public function __construct(private readonly ServicioPermisos $servicio_permisos)
    {
    }

    public function mostrar(Request $solicitud): Response
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'inventario.ver', $sucursal->id_sucursal);

        return Inertia::render('Inventario', [
            'sucursal_seleccionada' => $sucursal->only(['id_sucursal', 'nombre', 'clave']),
            'sucursales' => $this->sucursalesUsuario($solicitud),
            'recursos' => $this->recursosInventario($sucursal->id_sucursal),
            'movimientos' => $this->movimientos($sucursal->id_sucursal),
            'puede_ajustar' => $this->servicio_permisos->tiene($solicitud->user(), 'inventario.ajustar', $sucursal->id_sucursal),
        ]);
    }

    public function ajustarExistencia(Request $solicitud, string $tipo_recurso, int $id_recurso): RedirectResponse
    {
        abort_unless(in_array($tipo_recurso, ['productos', 'insumos'], true), 404);

        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'inventario.ajustar', $sucursal->id_sucursal);
        $datos = $solicitud->validate([
            'existencia_nueva' => ['required', 'numeric', 'min:0', 'max:99999999.9999'],
            'motivo' => ['required', 'string', 'min:5', 'max:450'],
        ]);

        $configuracion = $tipo_recurso === 'productos'
            ? ['tabla_existencias' => 'sucursales_productos', 'columna_recurso' => 'ref_producto', 'tabla_recurso' => 'productos', 'id_recurso' => 'id_producto', 'campo_movimiento' => 'ref_producto', 'accion' => 'ajustar_existencia_producto']
            : ['tabla_existencias' => 'sucursales_insumos', 'columna_recurso' => 'ref_insumo', 'tabla_recurso' => 'insumos', 'id_recurso' => 'id_insumo', 'campo_movimiento' => 'ref_insumo', 'accion' => 'ajustar_existencia_insumo'];

        $resultado = DB::transaction(function () use ($configuracion, $datos, $id_recurso, $sucursal): array {
            $recurso = DB::table($configuracion['tabla_recurso'])
                ->where($configuracion['id_recurso'], $id_recurso)
                ->where('activo', true)
                ->first([$configuracion['id_recurso'], 'nombre']);
            abort_unless($recurso, 404);

            $existencia = DB::table($configuracion['tabla_existencias'])
                ->where('ref_sucursal', $sucursal->id_sucursal)
                ->where($configuracion['columna_recurso'], $id_recurso)
                ->where('activo', true)
                ->lockForUpdate()
                ->first();
            abort_unless($existencia, 404);

            $anterior = (float) $existencia->existencia_actual;
            $posterior = (float) $datos['existencia_nueva'];
            abort_unless(abs($posterior - $anterior) >= 0.00005, 422, 'La existencia nueva debe ser diferente a la actual.');

            DB::table($configuracion['tabla_existencias'])
                ->where('ref_sucursal', $sucursal->id_sucursal)
                ->where($configuracion['columna_recurso'], $id_recurso)
                ->update(['existencia_actual' => $posterior, 'actualizado_en' => now()]);

            $id_movimiento = DB::table('movimientos_inventario')->insertGetId([
                'ref_sucursal' => $sucursal->id_sucursal,
                $configuracion['campo_movimiento'] => $id_recurso,
                'tipo_movimiento' => 'entrada_ajuste',
                'cantidad' => $posterior - $anterior,
                'existencia_anterior' => $anterior,
                'existencia_posterior' => $posterior,
                'descripcion' => 'Ajuste manual: '.$datos['motivo'],
                'registrado_en' => now(),
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            return ['id_movimiento' => $id_movimiento, 'nombre' => $recurso->nombre, 'anterior' => $anterior, 'posterior' => $posterior];
        });

        $this->registrar($solicitud, $configuracion['accion'], 'movimientos_inventario', $resultado['id_movimiento'], [
            'ref_recurso' => $id_recurso,
            'existencia_anterior' => $resultado['anterior'],
            'existencia_posterior' => $resultado['posterior'],
            'motivo' => $datos['motivo'],
        ], $sucursal->id_sucursal);

        return back()->with('exito', "La existencia de {$resultado['nombre']} fue ajustada.");
    }

    private function recursosInventario(int $id_sucursal): Collection
    {
        $productos = DB::table('sucursales_productos')
            ->join('productos', 'productos.id_producto', '=', 'sucursales_productos.ref_producto')
            ->where('sucursales_productos.ref_sucursal', $id_sucursal)
            ->where('sucursales_productos.activo', true)
            ->where('productos.activo', true)
            ->whereIn('productos.tipo_inventario', ['unidad', 'mixto'])
            ->select([
                'productos.id_producto as id_recurso', 'productos.nombre', 'productos.codigo', 'productos.tipo_inventario',
                'sucursales_productos.existencia_actual', 'sucursales_productos.existencia_minima',
                DB::raw("'producto' as tipo_recurso"), DB::raw("'unidades' as unidad"),
            ])->get();

        $insumos = DB::table('sucursales_insumos')
            ->join('insumos', 'insumos.id_insumo', '=', 'sucursales_insumos.ref_insumo')
            ->join('cat_unidades_medida', 'cat_unidades_medida.id_unidad_medida', '=', 'insumos.ref_unidad_medida')
            ->where('sucursales_insumos.ref_sucursal', $id_sucursal)
            ->where('sucursales_insumos.activo', true)
            ->where('insumos.activo', true)
            ->select([
                'insumos.id_insumo as id_recurso', 'insumos.nombre', 'insumos.codigo',
                'sucursales_insumos.existencia_actual', 'sucursales_insumos.existencia_minima',
                'cat_unidades_medida.abreviatura as unidad',
                DB::raw("'insumo' as tipo_recurso"), DB::raw("'receta' as tipo_inventario"),
            ])->get();

        return $productos->merge($insumos)
            ->map(fn (object $recurso) => [
                ...get_object_vars($recurso),
                'bajo_minimo' => (float) $recurso->existencia_minima > 0 && (float) $recurso->existencia_actual <= (float) $recurso->existencia_minima,
            ])
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function movimientos(int $id_sucursal): Collection
    {
        return DB::table('movimientos_inventario')
            ->leftJoin('productos', 'productos.id_producto', '=', 'movimientos_inventario.ref_producto')
            ->leftJoin('insumos', 'insumos.id_insumo', '=', 'movimientos_inventario.ref_insumo')
            ->where('movimientos_inventario.ref_sucursal', $id_sucursal)
            ->where('movimientos_inventario.activo', true)
            ->orderByDesc('movimientos_inventario.registrado_en')
            ->orderByDesc('movimientos_inventario.id_movimiento_inventario')
            ->limit(60)
            ->get([
                'movimientos_inventario.id_movimiento_inventario', 'movimientos_inventario.tipo_movimiento', 'movimientos_inventario.cantidad',
                'movimientos_inventario.existencia_anterior', 'movimientos_inventario.existencia_posterior', 'movimientos_inventario.descripcion',
                'movimientos_inventario.registrado_en', DB::raw('COALESCE(productos.nombre, insumos.nombre) as recurso'),
                DB::raw("CASE WHEN movimientos_inventario.ref_producto IS NULL THEN 'insumo' ELSE 'producto' END as tipo_recurso"),
            ]);
    }

    private function sucursalesUsuario(Request $solicitud): Collection
    {
        return Sucursal::query()->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)
            ->where('usuarios_sucursales.activo', true)->where('sucursales.activo', true)
            ->orderByDesc('usuarios_sucursales.es_principal')->orderBy('sucursales.nombre')
            ->get(['sucursales.id_sucursal', 'sucursales.nombre', 'sucursales.clave']);
    }

    private function sucursalSeleccionada(Request $solicitud): Sucursal
    {
        return Sucursal::query()->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)
            ->where('usuarios_sucursales.activo', true)->where('sucursales.activo', true)
            ->when($solicitud->integer('ref_sucursal'), fn ($consulta, int $id_sucursal) => $consulta->where('sucursales.id_sucursal', $id_sucursal))
            ->orderByDesc('usuarios_sucursales.es_principal')->select('sucursales.*')->firstOrFail();
    }

    private function autorizar(Request $solicitud, string $permiso, int $id_sucursal): void
    {
        abort_unless($this->servicio_permisos->tiene($solicitud->user(), $permiso, $id_sucursal), 403);
    }

    private function registrar(Request $solicitud, string $accion, string $entidad, int $id_entidad, array $datos_nuevos, int $id_sucursal): void
    {
        DB::table('bitacora_auditoria')->insert([
            'ref_usuario' => $solicitud->user()->id_usuario, 'ref_sucursal' => $id_sucursal, 'modulo' => 'inventario',
            'accion' => $accion, 'entidad' => $entidad, 'ref_entidad' => $id_entidad, 'datos_nuevos' => json_encode($datos_nuevos),
            'direccion_ip' => $solicitud->ip(), 'agente_usuario' => $solicitud->userAgent(), 'creado_en' => now(), 'actualizado_en' => now(),
        ]);
    }
}
