<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_unidades_medida', function (Blueprint $tabla) {
            $tabla->id('id_unidad_medida');
            $tabla->string('codigo', 20)->unique();
            $tabla->string('nombre', 80);
            $tabla->string('abreviatura', 12);
            $tabla->boolean('permite_decimales')->default(true);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('insumos', function (Blueprint $tabla) {
            $tabla->id('id_insumo');
            $tabla->foreignId('ref_negocio')->constrained('negocios', 'id_negocio')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_unidad_medida')->constrained('cat_unidades_medida', 'id_unidad_medida')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('codigo', 80)->nullable();
            $tabla->string('nombre', 160);
            $tabla->decimal('costo_unitario', 14, 4)->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_negocio', 'codigo'], 'insumos_negocio_codigo_unq');
            $tabla->index(['ref_negocio', 'nombre']);
        });

        Schema::create('sucursales_insumos', function (Blueprint $tabla) {
            $tabla->id('id_sucursal_insumo');
            $tabla->foreignId('ref_sucursal')->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_insumo')->constrained('insumos', 'id_insumo')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->decimal('existencia_actual', 14, 4)->default(0);
            $tabla->decimal('existencia_minima', 14, 4)->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_sucursal', 'ref_insumo'], 'suc_ins_unq');
        });

        Schema::create('recetas_productos', function (Blueprint $tabla) {
            $tabla->id('id_receta_producto');
            $tabla->foreignId('ref_producto')->constrained('productos', 'id_producto')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_insumo')->constrained('insumos', 'id_insumo')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->decimal('cantidad', 14, 4);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_producto', 'ref_insumo'], 'rec_prod_ins_unq');
        });

        $ahora = now();
        DB::table('cat_unidades_medida')->insert([
            ['codigo' => 'pieza', 'nombre' => 'Pieza', 'abreviatura' => 'pz', 'permite_decimales' => false, 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'gramo', 'nombre' => 'Gramo', 'abreviatura' => 'g', 'permite_decimales' => true, 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'kilogramo', 'nombre' => 'Kilogramo', 'abreviatura' => 'kg', 'permite_decimales' => true, 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'mililitro', 'nombre' => 'Mililitro', 'abreviatura' => 'ml', 'permite_decimales' => true, 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'litro', 'nombre' => 'Litro', 'abreviatura' => 'l', 'permite_decimales' => true, 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('recetas_productos');
        Schema::dropIfExists('sucursales_insumos');
        Schema::dropIfExists('insumos');
        Schema::dropIfExists('cat_unidades_medida');
    }
};
