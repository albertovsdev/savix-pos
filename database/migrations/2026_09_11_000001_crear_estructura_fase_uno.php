<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $tabla) {
            $tabla->id('id_negocio');
            $tabla->string('nombre', 120);
            $tabla->string('nombre_comercial', 120);
            $tabla->string('clave_folio', 12)->default('SX');
            $tabla->string('rfc', 20)->nullable();
            $tabla->string('correo', 191)->nullable();
            $tabla->string('telefono', 30)->nullable();
            $tabla->string('ruta_logo', 500)->nullable();
            $tabla->string('color_primario', 20)->default('#6C63FF');
            $tabla->string('color_secundario', 20)->default('#1A1A2E');
            $tabla->string('color_acento', 20)->default('#9993DD');
            $tabla->enum('tema_predeterminado', ['claro', 'oscuro', 'sistema'])->default('oscuro');
            $tabla->decimal('porcentaje_iva', 5, 2)->default(16.00);
            $tabla->boolean('precios_incluyen_iva')->default(true);
            $tabla->unsignedTinyInteger('ancho_ticket_mm')->default(80);
            $tabla->text('direccion_ticket')->nullable();
            $tabla->text('pie_ticket')->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('sucursales', function (Blueprint $tabla) {
            $tabla->id('id_sucursal');
            $tabla->foreignId('ref_negocio')->constrained('negocios', 'id_negocio')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('clave', 12);
            $tabla->string('nombre', 120);
            $tabla->string('telefono', 30)->nullable();
            $tabla->string('correo', 191)->nullable();
            $tabla->text('direccion')->nullable();
            $tabla->unsignedBigInteger('ultimo_folio_ticket')->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_negocio', 'clave']);
        });

        Schema::create('usuarios_sucursales', function (Blueprint $tabla) {
            $tabla->id('id_usuario_sucursal');
            $tabla->foreignId('ref_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_sucursal')->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->boolean('es_principal')->default(false);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_usuario', 'ref_sucursal']);
        });

        Schema::create('cat_roles', function (Blueprint $tabla) {
            $tabla->id('id_rol');
            $tabla->string('codigo', 80)->unique();
            $tabla->string('nombre', 120);
            $tabla->text('descripcion')->nullable();
            $tabla->boolean('es_sistema')->default(true);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('cat_permisos', function (Blueprint $tabla) {
            $tabla->id('id_permiso');
            $tabla->string('codigo', 120)->unique();
            $tabla->string('modulo', 80);
            $tabla->string('nombre', 120);
            $tabla->text('descripcion')->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('roles_permisos', function (Blueprint $tabla) {
            $tabla->id('id_rol_permiso');
            $tabla->foreignId('ref_rol')->constrained('cat_roles', 'id_rol')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_permiso')->constrained('cat_permisos', 'id_permiso')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_rol', 'ref_permiso']);
        });

        Schema::create('usuarios_roles', function (Blueprint $tabla) {
            $tabla->id('id_usuario_rol');
            $tabla->foreignId('ref_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_rol')->constrained('cat_roles', 'id_rol')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_sucursal')->nullable()->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->index(['ref_usuario', 'ref_sucursal']);
        });

        Schema::create('usuarios_permisos', function (Blueprint $tabla) {
            $tabla->id('id_usuario_permiso');
            $tabla->foreignId('ref_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_permiso')->constrained('cat_permisos', 'id_permiso')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_sucursal')->nullable()->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->enum('tipo_asignacion', ['permitir', 'denegar']);
            $tabla->string('motivo', 500)->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->index(['ref_usuario', 'ref_permiso', 'ref_sucursal']);
        });

        Schema::create('cat_modulos', function (Blueprint $tabla) {
            $tabla->id('id_modulo');
            $tabla->string('codigo', 80)->unique();
            $tabla->string('nombre', 120);
            $tabla->text('descripcion')->nullable();
            $tabla->unsignedSmallInteger('orden')->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('sucursales_modulos', function (Blueprint $tabla) {
            $tabla->id('id_sucursal_modulo');
            $tabla->foreignId('ref_sucursal')->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_modulo')->constrained('cat_modulos', 'id_modulo')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->boolean('habilitado')->default(true);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_sucursal', 'ref_modulo']);
        });

        Schema::create('bitacora_auditoria', function (Blueprint $tabla) {
            $tabla->id('id_bitacora_auditoria');
            $tabla->foreignId('ref_usuario')->nullable()->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->nullOnDelete();
            $tabla->foreignId('ref_sucursal')->nullable()->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->nullOnDelete();
            $tabla->string('modulo', 80);
            $tabla->string('accion', 120);
            $tabla->string('entidad', 120)->nullable();
            $tabla->unsignedBigInteger('ref_entidad')->nullable();
            $tabla->string('motivo', 500)->nullable();
            $tabla->json('datos_anteriores')->nullable();
            $tabla->json('datos_nuevos')->nullable();
            $tabla->ipAddress('direccion_ip')->nullable();
            $tabla->text('agente_usuario')->nullable();
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->index(['entidad', 'ref_entidad']);
            $tabla->index(['ref_usuario', 'creado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_auditoria');
        Schema::dropIfExists('sucursales_modulos');
        Schema::dropIfExists('cat_modulos');
        Schema::dropIfExists('usuarios_permisos');
        Schema::dropIfExists('usuarios_roles');
        Schema::dropIfExists('roles_permisos');
        Schema::dropIfExists('cat_permisos');
        Schema::dropIfExists('cat_roles');
        Schema::dropIfExists('usuarios_sucursales');
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('negocios');
    }
};
