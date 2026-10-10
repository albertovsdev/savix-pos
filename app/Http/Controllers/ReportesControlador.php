<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Services\ServicioPermisos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportesControlador extends Controller
{
    public function __construct(private readonly ServicioPermisos $servicio_permisos)
    {
    }

    public function mostrar(Request $solicitud): Response
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'reportes.ver', $sucursal->id_sucursal);
        $datos = $solicitud->validate([
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
        ]);
        $fecha_inicio = Carbon::parse($datos['fecha_inicio'] ?? now()->toDateString())->startOfDay();
        $fecha_fin = Carbon::parse($datos['fecha_fin'] ?? now()->toDateString())->endOfDay();
        abort_unless($fecha_inicio->lessThanOrEqualTo($fecha_fin), 422, 'La fecha inicial debe ser anterior o igual a la fecha final.');

        $ventas = $this->ventas($sucursal->id_sucursal, $fecha_inicio, $fecha_fin);
        $compras = $this->compras($sucursal->id_sucursal, $fecha_inicio, $fecha_fin);

        return Inertia::render('Reportes', [
            'sucursal_seleccionada' => $sucursal->only(['id_sucursal', 'nombre', 'clave']),
            'sucursales' => $this->sucursalesUsuario($solicitud),
            'filtros' => ['fecha_inicio' => $fecha_inicio->toDateString(), 'fecha_fin' => $fecha_fin->toDateString()],
            'resumen' => [
                'ventas_cerradas' => $ventas['cantidad'],
                'importe_ventas' => $ventas['importe'],
                'ticket_promedio' => $ventas['cantidad'] > 0 ? round($ventas['importe'] / $ventas['cantidad'], 2) : 0,
                'importe_compras' => $compras,
                'alertas_inventario' => $this->alertasInventario($sucursal->id_sucursal)->count(),
            ],
            'pagos_por_tipo' => $ventas['pagos_por_tipo'],
            'productos_vendidos' => $this->productosVendidos($sucursal->id_sucursal, $fecha_inicio, $fecha_fin),
            'alertas_inventario' => $this->alertasInventario($sucursal->id_sucursal),
        ]);
    }

    private function ventas(int $id_sucursal, Carbon $fecha_inicio, Carbon $fecha_fin): array
    {
        $pagos = DB::table('pagos_partes_cuenta_pedidos')
            ->join('partes_cuenta_pedidos', 'partes_cuenta_pedidos.id_parte_cuenta_pedido', '=', 'pagos_partes_cuenta_pedidos.ref_parte_cuenta_pedido')
            ->join('pedidos', 'pedidos.id_pedido', '=', 'partes_cuenta_pedidos.ref_pedido')
            ->where('pedidos.ref_sucursal', $id_sucursal)->where('pedidos.estado', 'cerrado')->where('pedidos.activo', true)
            ->where('pagos_partes_cuenta_pedidos.activo', true)->whereBetween('pedidos.cerrado_en', [$fecha_inicio, $fecha_fin]);
        $pagos_por_tipo = (clone $pagos)->select('pagos_partes_cuenta_pedidos.tipo_pago', DB::raw('SUM(pagos_partes_cuenta_pedidos.importe) as importe'))
            ->groupBy('pagos_partes_cuenta_pedidos.tipo_pago')->orderBy('pagos_partes_cuenta_pedidos.tipo_pago')->get();

        return [
            'cantidad' => DB::table('pedidos')->where('ref_sucursal', $id_sucursal)->where('estado', 'cerrado')->where('activo', true)
                ->whereBetween('cerrado_en', [$fecha_inicio, $fecha_fin])->count(),
            'importe' => (float) $pagos->sum('pagos_partes_cuenta_pedidos.importe'),
            'pagos_por_tipo' => $pagos_por_tipo,
        ];
    }

    private function compras(int $id_sucursal, Carbon $fecha_inicio, Carbon $fecha_fin): float
    {
        return (float) DB::table('compras')->where('ref_sucursal', $id_sucursal)->where('estado', 'registrada')->where('activo', true)
            ->whereBetween('fecha_compra', [$fecha_inicio->toDateString(), $fecha_fin->toDateString()])->sum('importe_total');
    }

    private function productosVendidos(int $id_sucursal, Carbon $fecha_inicio, Carbon $fecha_fin): Collection
    {
        return DB::table('detalles_rondas_pedidos')
            ->join('rondas_pedidos', 'rondas_pedidos.id_ronda_pedido', '=', 'detalles_rondas_pedidos.ref_ronda_pedido')
            ->join('pedidos', 'pedidos.id_pedido', '=', 'rondas_pedidos.ref_pedido')
            ->where('pedidos.ref_sucursal', $id_sucursal)->where('pedidos.estado', 'cerrado')->where('pedidos.activo', true)
            ->where('detalles_rondas_pedidos.estado', 'activo')->where('detalles_rondas_pedidos.activo', true)
            ->whereBetween('pedidos.cerrado_en', [$fecha_inicio, $fecha_fin])
            ->groupBy('detalles_rondas_pedidos.ref_producto', 'detalles_rondas_pedidos.nombre_producto')
            ->orderByDesc('importe_vendido')->limit(10)
            ->get([
                'detalles_rondas_pedidos.ref_producto', 'detalles_rondas_pedidos.nombre_producto',
                DB::raw('SUM(detalles_rondas_pedidos.cantidad) as cantidad_vendida'), DB::raw('SUM(detalles_rondas_pedidos.importe_total) as importe_vendido'),
            ]);
    }

    private function alertasInventario(int $id_sucursal): Collection
    {
        $productos = DB::table('sucursales_productos')->join('productos', 'productos.id_producto', '=', 'sucursales_productos.ref_producto')
            ->where('sucursales_productos.ref_sucursal', $id_sucursal)->where('sucursales_productos.activo', true)->where('productos.activo', true)
            ->whereIn('productos.tipo_inventario', ['unidad', 'mixto'])->where('sucursales_productos.existencia_minima', '>', 0)
            ->whereColumn('sucursales_productos.existencia_actual', '<=', 'sucursales_productos.existencia_minima')
            ->get(['productos.nombre', 'sucursales_productos.existencia_actual', 'sucursales_productos.existencia_minima', DB::raw("'Producto' as tipo")]);
        $insumos = DB::table('sucursales_insumos')->join('insumos', 'insumos.id_insumo', '=', 'sucursales_insumos.ref_insumo')
            ->where('sucursales_insumos.ref_sucursal', $id_sucursal)->where('sucursales_insumos.activo', true)->where('insumos.activo', true)
            ->where('sucursales_insumos.existencia_minima', '>', 0)->whereColumn('sucursales_insumos.existencia_actual', '<=', 'sucursales_insumos.existencia_minima')
            ->get(['insumos.nombre', 'sucursales_insumos.existencia_actual', 'sucursales_insumos.existencia_minima', DB::raw("'Insumo' as tipo")]);

        return $productos->merge($insumos)->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    private function sucursalesUsuario(Request $solicitud): Collection
    {
        return Sucursal::query()->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)->where('usuarios_sucursales.activo', true)->where('sucursales.activo', true)
            ->orderByDesc('usuarios_sucursales.es_principal')->orderBy('sucursales.nombre')->get(['sucursales.id_sucursal', 'sucursales.nombre', 'sucursales.clave']);
    }

    private function sucursalSeleccionada(Request $solicitud): Sucursal
    {
        return Sucursal::query()->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)->where('usuarios_sucursales.activo', true)->where('sucursales.activo', true)
            ->when($solicitud->integer('ref_sucursal'), fn ($consulta, int $id_sucursal) => $consulta->where('sucursales.id_sucursal', $id_sucursal))
            ->orderByDesc('usuarios_sucursales.es_principal')->select('sucursales.*')->firstOrFail();
    }

    private function autorizar(Request $solicitud, string $permiso, int $id_sucursal): void
    {
        abort_unless($this->servicio_permisos->tiene($solicitud->user(), $permiso, $id_sucursal), 403);
    }
}
