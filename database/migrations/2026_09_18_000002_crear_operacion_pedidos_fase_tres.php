<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $tabla) {
            $tabla->id('id_mesa');
            $tabla->foreignId('ref_sucursal')->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->string('nombre', 80);
            $tabla->unsignedSmallInteger('orden')->default(0);
            $tabla->enum('estado', ['libre', 'ocupada', 'inactiva'])->default('libre');
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_sucursal', 'nombre'], 'mesas_sucursal_nombre_unq');
        });

        Schema::create('pedidos', function (Blueprint $tabla) {
            $tabla->id('id_pedido');
            $tabla->foreignId('ref_sucursal')->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_mesa')->nullable()->constrained('mesas', 'id_mesa')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_usuario_apertura')->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->enum('tipo_servicio', ['mesa', 'mostrador']);
            $tabla->enum('estado', ['abierto', 'cerrado', 'cancelado'])->default('abierto');
            $tabla->string('referencia_mostrador', 80)->nullable();
            $tabla->text('nota')->nullable();
            $tabla->timestamp('abierto_en')->useCurrent();
            $tabla->timestamp('cerrado_en')->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->index(['ref_sucursal', 'estado']);
            $tabla->index(['ref_mesa', 'estado']);
        });

        Schema::create('rondas_pedidos', function (Blueprint $tabla) {
            $tabla->id('id_ronda_pedido');
            $tabla->foreignId('ref_pedido')->constrained('pedidos', 'id_pedido')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_usuario_creacion')->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->unsignedSmallInteger('numero_ronda');
            $tabla->enum('estado', ['borrador', 'enviada', 'cancelada'])->default('borrador');
            $tabla->decimal('importe_total', 14, 2)->default(0);
            $tabla->timestamp('enviada_en')->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_pedido', 'numero_ronda'], 'rondas_pedido_numero_unq');
        });

        Schema::create('detalles_rondas_pedidos', function (Blueprint $tabla) {
            $tabla->id('id_detalle_ronda_pedido');
            $tabla->foreignId('ref_ronda_pedido')->constrained('rondas_pedidos', 'id_ronda_pedido')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_producto')->constrained('productos', 'id_producto')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->decimal('cantidad', 12, 3);
            $tabla->string('nombre_producto', 160);
            $tabla->decimal('precio_unitario', 14, 2);
            $tabla->decimal('importe_extras', 14, 2)->default(0);
            $tabla->decimal('importe_total', 14, 2);
            $tabla->text('nota_preparacion')->nullable();
            $tabla->enum('estado', ['activo', 'cancelado'])->default('activo');
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('detalles_rondas_pedidos_modificadores', function (Blueprint $tabla) {
            $tabla->id('id_detalle_ronda_pedido_modificador');
            $tabla->foreignId('ref_detalle_ronda_pedido');
            $tabla->foreign('ref_detalle_ronda_pedido', 'drpm_detalle_fk')->references('id_detalle_ronda_pedido')->on('detalles_rondas_pedidos')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_opcion_modificador_producto')->nullable();
            $tabla->foreign('ref_opcion_modificador_producto', 'drpm_opcion_fk')->references('id_opcion_modificador_producto')->on('opciones_modificadores_productos')->cascadeOnUpdate()->nullOnDelete();
            $tabla->foreignId('ref_insumo')->nullable();
            $tabla->foreign('ref_insumo', 'drpm_insumo_fk')->references('id_insumo')->on('insumos')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('nombre_opcion', 120);
            $tabla->enum('tipo_modificacion', ['eliminar_insumo', 'agregar_insumo', 'nota']);
            $tabla->decimal('cantidad_insumo', 14, 4)->default(0);
            $tabla->decimal('precio_adicional', 12, 2)->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('detalles_rondas_pedidos_areas_preparacion', function (Blueprint $tabla) {
            $tabla->id('id_detalle_ronda_pedido_area_preparacion');
            $tabla->foreignId('ref_detalle_ronda_pedido');
            $tabla->foreign('ref_detalle_ronda_pedido', 'drap_detalle_fk')->references('id_detalle_ronda_pedido')->on('detalles_rondas_pedidos')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_area_preparacion');
            $tabla->foreign('ref_area_preparacion', 'drap_area_fk')->references('id_area_preparacion')->on('areas_preparacion')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->enum('estado_preparacion', ['pendiente', 'en_preparacion', 'listo', 'entregado', 'cancelado'])->default('pendiente');
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_detalle_ronda_pedido', 'ref_area_preparacion'], 'det_ronda_area_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_rondas_pedidos_areas_preparacion');
        Schema::dropIfExists('detalles_rondas_pedidos_modificadores');
        Schema::dropIfExists('detalles_rondas_pedidos');
        Schema::dropIfExists('rondas_pedidos');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('mesas');
    }
};
