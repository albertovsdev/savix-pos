<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos_modificadores_productos', function (Blueprint $tabla) {
            $tabla->id('id_grupo_modificador_producto');
            $tabla->foreignId('ref_producto')->constrained('productos', 'id_producto')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->string('nombre', 120);
            $tabla->unsignedTinyInteger('minimo_selecciones')->default(0);
            $tabla->unsignedTinyInteger('maximo_selecciones')->default(1);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_producto', 'nombre'], 'grupo_mod_prod_unq');
        });

        Schema::create('opciones_modificadores_productos', function (Blueprint $tabla) {
            $tabla->id('id_opcion_modificador_producto');
            $tabla->foreignId('ref_grupo_modificador_producto')->constrained('grupos_modificadores_productos', 'id_grupo_modificador_producto', 'opc_mod_grupo_fk')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_insumo')->nullable()->constrained('insumos', 'id_insumo')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('nombre', 120);
            $tabla->enum('tipo_modificacion', ['eliminar_insumo', 'agregar_insumo', 'nota'])->default('nota');
            $tabla->decimal('cantidad_insumo', 14, 4)->default(0);
            $tabla->decimal('precio_adicional', 12, 2)->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_grupo_modificador_producto', 'nombre'], 'opc_mod_prod_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones_modificadores_productos');
        Schema::dropIfExists('grupos_modificadores_productos');
    }
};
