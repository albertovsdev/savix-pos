<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelaciones_detalles_pedidos', function (Blueprint $tabla) {
            $tabla->id('id_cancelacion_detalle_pedido');
            $tabla->foreignId('ref_detalle_ronda_pedido');
            $tabla->foreign('ref_detalle_ronda_pedido', 'cdp_detalle_fk')->references('id_detalle_ronda_pedido')->on('detalles_rondas_pedidos')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_sucursal');
            $tabla->foreign('ref_sucursal', 'cdp_sucursal_fk')->references('id_sucursal')->on('sucursales')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_usuario_solicitud');
            $tabla->foreign('ref_usuario_solicitud', 'cdp_solicitud_fk')->references('id_usuario')->on('usuarios')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_usuario_autorizacion');
            $tabla->foreign('ref_usuario_autorizacion', 'cdp_autorizacion_fk')->references('id_usuario')->on('usuarios')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('motivo', 500);
            $tabla->boolean('fue_preparado')->default(false);
            $tabla->timestamp('cancelado_en')->useCurrent();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique('ref_detalle_ronda_pedido', 'cancelacion_detalle_unq');
        });

        Schema::create('mermas_pedidos', function (Blueprint $tabla) {
            $tabla->id('id_merma_pedido');
            $tabla->foreignId('ref_cancelacion_detalle_pedido');
            $tabla->foreign('ref_cancelacion_detalle_pedido', 'merma_cancelacion_fk')->references('id_cancelacion_detalle_pedido')->on('cancelaciones_detalles_pedidos')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_sucursal');
            $tabla->foreign('ref_sucursal', 'merma_sucursal_fk')->references('id_sucursal')->on('sucursales')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_usuario_registro');
            $tabla->foreign('ref_usuario_registro', 'merma_usuario_fk')->references('id_usuario')->on('usuarios')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('descripcion', 500);
            $tabla->timestamp('registrada_en')->useCurrent();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique('ref_cancelacion_detalle_pedido', 'merma_cancelacion_unq');
        });

        Schema::create('partes_cuenta_pedidos', function (Blueprint $tabla) {
            $tabla->id('id_parte_cuenta_pedido');
            $tabla->foreignId('ref_pedido');
            $tabla->foreign('ref_pedido', 'parte_pedido_fk')->references('id_pedido')->on('pedidos')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->string('nombre', 120);
            $tabla->unsignedSmallInteger('orden')->default(0);
            $tabla->enum('estado', ['abierta', 'pagada'])->default('abierta');
            $tabla->timestamp('pagada_en')->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_pedido', 'nombre'], 'parte_pedido_nombre_unq');
        });

        Schema::table('detalles_rondas_pedidos', function (Blueprint $tabla) {
            $tabla->foreignId('ref_parte_cuenta_pedido')->nullable()->after('ref_ronda_pedido');
            $tabla->foreign('ref_parte_cuenta_pedido', 'detalle_parte_cuenta_fk')->references('id_parte_cuenta_pedido')->on('partes_cuenta_pedidos')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('pagos_partes_cuenta_pedidos', function (Blueprint $tabla) {
            $tabla->id('id_pago_parte_cuenta_pedido');
            $tabla->foreignId('ref_parte_cuenta_pedido');
            $tabla->foreign('ref_parte_cuenta_pedido', 'pago_parte_fk')->references('id_parte_cuenta_pedido')->on('partes_cuenta_pedidos')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_usuario_registro');
            $tabla->foreign('ref_usuario_registro', 'pago_usuario_fk')->references('id_usuario')->on('usuarios')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->enum('tipo_pago', ['efectivo', 'tarjeta']);
            $tabla->decimal('importe', 14, 2);
            $tabla->string('referencia', 120)->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('pedidos', function (Blueprint $tabla) {
            $tabla->string('folio_ticket', 50)->nullable()->unique('pedidos_folio_ticket_unq')->after('referencia_mostrador');
            $tabla->timestamp('emitido_ticket_en')->nullable()->after('cerrado_en');
        });

        DB::table('pedidos')->orderBy('id_pedido')->each(function (object $pedido): void {
            $id_parte = DB::table('partes_cuenta_pedidos')->insertGetId([
                'ref_pedido' => $pedido->id_pedido,
                'nombre' => 'Cuenta general',
                'orden' => 1,
                'estado' => 'abierta',
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);
            DB::table('detalles_rondas_pedidos')
                ->whereIn('ref_ronda_pedido', DB::table('rondas_pedidos')->where('ref_pedido', $pedido->id_pedido)->pluck('id_ronda_pedido'))
                ->whereNull('ref_parte_cuenta_pedido')
                ->update(['ref_parte_cuenta_pedido' => $id_parte, 'actualizado_en' => now()]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_partes_cuenta_pedidos');
        Schema::table('detalles_rondas_pedidos', function (Blueprint $tabla) {
            $tabla->dropForeign('detalle_parte_cuenta_fk');
            $tabla->dropColumn('ref_parte_cuenta_pedido');
        });
        Schema::dropIfExists('partes_cuenta_pedidos');
        Schema::dropIfExists('mermas_pedidos');
        Schema::dropIfExists('cancelaciones_detalles_pedidos');
        Schema::table('pedidos', function (Blueprint $tabla) {
            $tabla->dropUnique('pedidos_folio_ticket_unq');
            $tabla->dropColumn(['folio_ticket', 'emitido_ticket_en']);
        });
    }
};
