<?php

namespace App\Services;

use App\Models\DetalleRondaPedido;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServicioInventario
{
    public function registrarEntradaCompra(int $id_sucursal, string $tipo_recurso, int $id_recurso, float $cantidad, string $descripcion): void
    {
        abort_unless($cantidad > 0, 422);
        abort_unless(in_array($tipo_recurso, ['producto', 'insumo'], true), 422);

        $configuracion = $tipo_recurso === 'producto'
            ? ['tabla' => 'sucursales_productos', 'columna' => 'ref_producto', 'id_existencia' => 'id_sucursal_producto', 'campo_movimiento' => 'ref_producto']
            : ['tabla' => 'sucursales_insumos', 'columna' => 'ref_insumo', 'id_existencia' => 'id_sucursal_insumo', 'campo_movimiento' => 'ref_insumo'];
        $existencia = DB::table($configuracion['tabla'])
            ->where('ref_sucursal', $id_sucursal)
            ->where($configuracion['columna'], $id_recurso)
            ->where('activo', true)
            ->lockForUpdate()
            ->first([$configuracion['id_existencia'], 'existencia_actual']);
        abort_unless($existencia, 422, 'El recurso no está disponible para esta sucursal.');

        $anterior = (float) $existencia->existencia_actual;
        $posterior = $anterior + $cantidad;
        DB::table($configuracion['tabla'])->where($configuracion['id_existencia'], $existencia->{$configuracion['id_existencia']})->update([
            'existencia_actual' => $posterior,
            'actualizado_en' => now(),
        ]);
        $this->guardarMovimiento($id_sucursal, null, 'entrada_compra', $cantidad, $anterior, $posterior, $descripcion,
            $tipo_recurso === 'producto' ? $id_recurso : null,
            $tipo_recurso === 'insumo' ? $id_recurso : null,
            null,
        );
    }

    public function descontarPorVenta(DetalleRondaPedido $detalle, int $id_sucursal): void
    {
        if (DB::table('movimientos_inventario')->where('ref_detalle_ronda_pedido', $detalle->id_detalle_ronda_pedido)->where('tipo_movimiento', 'salida_venta')->exists()) {
            return;
        }

        $producto = DB::table('productos')->where('id_producto', $detalle->ref_producto)->first(['id_producto', 'nombre', 'tipo_inventario']);
        if (! $producto || $producto->tipo_inventario === 'sin_control') {
            return;
        }

        if (in_array($producto->tipo_inventario, ['unidad', 'mixto'], true)) {
            $this->registrarMovimientoProducto($detalle, $id_sucursal, -1 * (float) $detalle->cantidad, 'salida_venta', "Venta de {$detalle->nombre_producto}");
        }

        if (in_array($producto->tipo_inventario, ['receta', 'mixto'], true)) {
            foreach ($this->consumosReceta($detalle) as $consumo) {
                if ($consumo['cantidad'] > 0) {
                    $this->registrarMovimientoInsumo($detalle, $id_sucursal, $consumo['ref_insumo'], -1 * $consumo['cantidad'], 'salida_venta', "Receta de {$detalle->nombre_producto}");
                }
            }
        }
    }

    public function devolverPorCancelacion(DetalleRondaPedido $detalle, int $id_sucursal): void
    {
        $salidas = DB::table('movimientos_inventario')
            ->where('ref_detalle_ronda_pedido', $detalle->id_detalle_ronda_pedido)
            ->where('tipo_movimiento', 'salida_venta')
            ->where('activo', true)
            ->get();

        foreach ($salidas as $salida) {
            if (DB::table('movimientos_inventario')->where('ref_movimiento_origen', $salida->id_movimiento_inventario)->exists()) {
                continue;
            }
            if ($salida->ref_producto) {
                $this->registrarMovimientoProducto($detalle, $id_sucursal, abs((float) $salida->cantidad), 'entrada_cancelacion', "Cancelación no preparada de {$detalle->nombre_producto}", $salida->id_movimiento_inventario);
            }
            if ($salida->ref_insumo) {
                $this->registrarMovimientoInsumo($detalle, $id_sucursal, $salida->ref_insumo, abs((float) $salida->cantidad), 'entrada_cancelacion', "Cancelación no preparada de {$detalle->nombre_producto}", $salida->id_movimiento_inventario);
            }
        }
    }

    private function consumosReceta(DetalleRondaPedido $detalle): Collection
    {
        $receta = DB::table('recetas_productos')->where('ref_producto', $detalle->ref_producto)->where('activo', true)->get(['ref_insumo', 'cantidad']);
        $modificadores = DB::table('detalles_rondas_pedidos_modificadores')
            ->where('ref_detalle_ronda_pedido', $detalle->id_detalle_ronda_pedido)
            ->where('activo', true)
            ->whereNotNull('ref_insumo')
            ->get(['ref_insumo', 'tipo_modificacion', 'cantidad_insumo']);

        return $receta->map(function (object $ingrediente) use ($detalle, $modificadores): array {
            $eliminado = $modificadores->where('tipo_modificacion', 'eliminar_insumo')->where('ref_insumo', $ingrediente->ref_insumo)->isNotEmpty();
            $cantidad = $eliminado ? 0 : ((float) $ingrediente->cantidad * (float) $detalle->cantidad);
            $extras = $modificadores->where('tipo_modificacion', 'agregar_insumo')->where('ref_insumo', $ingrediente->ref_insumo)->sum('cantidad_insumo');

            return ['ref_insumo' => $ingrediente->ref_insumo, 'cantidad' => $cantidad + ((float) $extras * (float) $detalle->cantidad)];
        })->merge(
            $modificadores
                ->where('tipo_modificacion', 'agregar_insumo')
                ->reject(fn (object $modificador) => $receta->pluck('ref_insumo')->contains($modificador->ref_insumo))
                ->map(fn (object $modificador): array => ['ref_insumo' => $modificador->ref_insumo, 'cantidad' => (float) $modificador->cantidad_insumo * (float) $detalle->cantidad]),
        );
    }

    private function registrarMovimientoProducto(DetalleRondaPedido $detalle, int $id_sucursal, float $cantidad, string $tipo, string $descripcion, ?int $id_origen = null): void
    {
        $existencia = DB::table('sucursales_productos')
            ->where('ref_sucursal', $id_sucursal)
            ->where('ref_producto', $detalle->ref_producto)
            ->lockForUpdate()
            ->first(['id_sucursal_producto', 'existencia_actual']);
        abort_unless($existencia, 422);
        $anterior = (float) $existencia->existencia_actual;
        $posterior = $anterior + $cantidad;
        DB::table('sucursales_productos')->where('id_sucursal_producto', $existencia->id_sucursal_producto)->update(['existencia_actual' => $posterior, 'actualizado_en' => now()]);
        $this->guardarMovimiento($id_sucursal, $detalle, $tipo, $cantidad, $anterior, $posterior, $descripcion, $detalle->ref_producto, null, $id_origen);
    }

    private function registrarMovimientoInsumo(DetalleRondaPedido $detalle, int $id_sucursal, int $id_insumo, float $cantidad, string $tipo, string $descripcion, ?int $id_origen = null): void
    {
        $existencia = DB::table('sucursales_insumos')
            ->where('ref_sucursal', $id_sucursal)
            ->where('ref_insumo', $id_insumo)
            ->lockForUpdate()
            ->first(['id_sucursal_insumo', 'existencia_actual']);
        abort_unless($existencia, 422);
        $anterior = (float) $existencia->existencia_actual;
        $posterior = $anterior + $cantidad;
        DB::table('sucursales_insumos')->where('id_sucursal_insumo', $existencia->id_sucursal_insumo)->update(['existencia_actual' => $posterior, 'actualizado_en' => now()]);
        $this->guardarMovimiento($id_sucursal, $detalle, $tipo, $cantidad, $anterior, $posterior, $descripcion, null, $id_insumo, $id_origen);
    }

    private function guardarMovimiento(int $id_sucursal, ?DetalleRondaPedido $detalle, string $tipo, float $cantidad, float $anterior, float $posterior, string $descripcion, ?int $id_producto, ?int $id_insumo, ?int $id_origen): void
    {
        DB::table('movimientos_inventario')->insert([
            'ref_sucursal' => $id_sucursal,
            'ref_producto' => $id_producto,
            'ref_insumo' => $id_insumo,
            'ref_detalle_ronda_pedido' => $detalle?->id_detalle_ronda_pedido,
            'ref_movimiento_origen' => $id_origen,
            'tipo_movimiento' => $tipo,
            'cantidad' => $cantidad,
            'existencia_anterior' => $anterior,
            'existencia_posterior' => $posterior,
            'descripcion' => $descripcion,
            'registrado_en' => now(),
            'activo' => true,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
    }
}
