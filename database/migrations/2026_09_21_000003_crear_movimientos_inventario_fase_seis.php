<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sucursales_productos', function (Blueprint $tabla) {
            $tabla->decimal('existencia_actual', 14, 4)->default(0)->after('habilitado');
            $tabla->decimal('existencia_minima', 14, 4)->default(0)->after('existencia_actual');
        });

        Schema::create('movimientos_inventario', function (Blueprint $tabla) {
            $tabla->id('id_movimiento_inventario');
            $tabla->foreignId('ref_sucursal');
            $tabla->foreign('ref_sucursal', 'mov_inv_sucursal_fk')->references('id_sucursal')->on('sucursales')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_producto')->nullable();
            $tabla->foreign('ref_producto', 'mov_inv_producto_fk')->references('id_producto')->on('productos')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_insumo')->nullable();
            $tabla->foreign('ref_insumo', 'mov_inv_insumo_fk')->references('id_insumo')->on('insumos')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_detalle_ronda_pedido')->nullable();
            $tabla->foreign('ref_detalle_ronda_pedido', 'mov_inv_detalle_fk')->references('id_detalle_ronda_pedido')->on('detalles_rondas_pedidos')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_movimiento_origen')->nullable();
            $tabla->foreign('ref_movimiento_origen', 'mov_inv_origen_fk')->references('id_movimiento_inventario')->on('movimientos_inventario')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->enum('tipo_movimiento', ['entrada_inicial', 'entrada_ajuste', 'entrada_compra', 'salida_venta', 'entrada_cancelacion', 'salida_merma']);
            $tabla->decimal('cantidad', 14, 4);
            $tabla->decimal('existencia_anterior', 14, 4);
            $tabla->decimal('existencia_posterior', 14, 4);
            $tabla->string('descripcion', 500);
            $tabla->timestamp('registrado_en')->useCurrent();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_detalle_ronda_pedido', 'ref_producto', 'ref_insumo', 'tipo_movimiento'], 'mov_inv_detalle_recurso_tipo_unq');
            $tabla->unique('ref_movimiento_origen', 'mov_inv_origen_unq');
            $tabla->index(['ref_sucursal', 'registrado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
        Schema::table('sucursales_productos', function (Blueprint $tabla) {
            $tabla->dropColumn(['existencia_actual', 'existencia_minima']);
        });
    }
};
