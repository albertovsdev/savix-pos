<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE movimientos_inventario MODIFY tipo_movimiento ENUM('entrada_inicial', 'entrada_ajuste', 'entrada_compra', 'salida_venta', 'entrada_cancelacion', 'salida_merma') NOT NULL");
        }

        Schema::create('proveedores', function (Blueprint $tabla) {
            $tabla->id('id_proveedor');
            $tabla->foreignId('ref_negocio')->constrained('negocios', 'id_negocio')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('codigo', 80)->nullable();
            $tabla->string('nombre', 160);
            $tabla->string('contacto', 160)->nullable();
            $tabla->string('telefono', 30)->nullable();
            $tabla->string('correo', 191)->nullable();
            $tabla->text('direccion')->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_negocio', 'codigo'], 'proveedor_negocio_codigo_unq');
            $tabla->index(['ref_negocio', 'nombre']);
        });

        Schema::create('compras', function (Blueprint $tabla) {
            $tabla->id('id_compra');
            $tabla->foreignId('ref_sucursal')->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_proveedor')->nullable()->constrained('proveedores', 'id_proveedor')->cascadeOnUpdate()->nullOnDelete();
            $tabla->foreignId('ref_usuario_registro')->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('folio_proveedor', 120)->nullable();
            $tabla->date('fecha_compra');
            $tabla->decimal('subtotal', 14, 2)->default(0);
            $tabla->decimal('importe_iva', 14, 2)->default(0);
            $tabla->decimal('importe_total', 14, 2)->default(0);
            $tabla->text('notas')->nullable();
            $tabla->enum('estado', ['registrada', 'cancelada'])->default('registrada');
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->index(['ref_sucursal', 'fecha_compra']);
            $tabla->index(['ref_proveedor', 'fecha_compra']);
        });

        Schema::create('detalles_compras', function (Blueprint $tabla) {
            $tabla->id('id_detalle_compra');
            $tabla->foreignId('ref_compra')->constrained('compras', 'id_compra')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_producto')->nullable()->constrained('productos', 'id_producto')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_insumo')->nullable()->constrained('insumos', 'id_insumo')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('nombre_recurso', 160);
            $tabla->decimal('cantidad', 14, 4);
            $tabla->decimal('costo_unitario', 14, 4);
            $tabla->decimal('importe_total', 14, 2);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->index(['ref_compra', 'ref_producto']);
            $tabla->index(['ref_compra', 'ref_insumo']);
        });

        $ahora = now();
        DB::table('cat_permisos')->upsert([
            ['codigo' => 'compras.ver', 'modulo' => 'compras', 'nombre' => 'Ver proveedores y compras', 'descripcion' => null, 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'compras.registrar', 'modulo' => 'compras', 'nombre' => 'Registrar proveedores y compras', 'descripcion' => null, 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
        ], ['codigo'], ['modulo', 'nombre', 'descripcion', 'activo', 'actualizado_en']);

        $roles = DB::table('cat_roles')->whereIn('codigo', ['dev', 'admin', 'inventario'])->pluck('id_rol', 'codigo');
        $permisos = DB::table('cat_permisos')->whereIn('codigo', ['compras.ver', 'compras.registrar'])->pluck('id_permiso', 'codigo');
        $asignaciones = [];
        foreach (['dev', 'admin', 'inventario'] as $codigo_rol) {
            foreach (['compras.ver', 'compras.registrar'] as $codigo_permiso) {
                if (isset($roles[$codigo_rol], $permisos[$codigo_permiso])) {
                    $asignaciones[] = ['ref_rol' => $roles[$codigo_rol], 'ref_permiso' => $permisos[$codigo_permiso], 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora];
                }
            }
        }
        DB::table('roles_permisos')->upsert($asignaciones, ['ref_rol', 'ref_permiso'], ['activo', 'actualizado_en']);
    }

    public function down(): void
    {
        DB::table('roles_permisos')->whereIn('ref_permiso', DB::table('cat_permisos')->whereIn('codigo', ['compras.ver', 'compras.registrar'])->pluck('id_permiso'))->delete();
        DB::table('cat_permisos')->whereIn('codigo', ['compras.ver', 'compras.registrar'])->delete();
        Schema::dropIfExists('detalles_compras');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('proveedores');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE movimientos_inventario MODIFY tipo_movimiento ENUM('entrada_inicial', 'entrada_ajuste', 'salida_venta', 'entrada_cancelacion', 'salida_merma') NOT NULL");
        }
    }
};
