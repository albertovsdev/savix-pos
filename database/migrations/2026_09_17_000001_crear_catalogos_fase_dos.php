<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_productos', function (Blueprint $tabla) {
            $tabla->id('id_categoria_producto');
            $tabla->foreignId('ref_negocio')->constrained('negocios', 'id_negocio')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('nombre', 120);
            $tabla->string('descripcion', 500)->nullable();
            $tabla->unsignedSmallInteger('orden')->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_negocio', 'nombre']);
        });

        Schema::create('sucursales_categorias_productos', function (Blueprint $tabla) {
            $tabla->id('id_sucursal_categoria_producto');
            $tabla->foreignId('ref_sucursal')->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_categoria_producto')->constrained('categorias_productos', 'id_categoria_producto')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->boolean('habilitada')->default(true);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_sucursal', 'ref_categoria_producto'], 'suc_cat_prod_unq');
        });

        Schema::create('areas_preparacion', function (Blueprint $tabla) {
            $tabla->id('id_area_preparacion');
            $tabla->foreignId('ref_sucursal')->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->string('nombre', 120);
            $tabla->string('codigo', 40);
            $tabla->unsignedSmallInteger('orden')->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_sucursal', 'codigo']);
        });

        Schema::create('productos', function (Blueprint $tabla) {
            $tabla->id('id_producto');
            $tabla->foreignId('ref_negocio')->constrained('negocios', 'id_negocio')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->foreignId('ref_categoria_producto')->nullable()->constrained('categorias_productos', 'id_categoria_producto')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->string('codigo', 80)->nullable();
            $tabla->string('nombre', 160);
            $tabla->text('descripcion')->nullable();
            $tabla->decimal('precio_compra', 12, 2)->default(0);
            $tabla->decimal('precio_venta', 12, 2);
            $tabla->enum('tipo_inventario', ['sin_control', 'unidad', 'receta', 'mixto'])->default('sin_control');
            $tabla->string('ruta_imagen', 500)->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_negocio', 'codigo']);
            $tabla->index(['ref_negocio', 'nombre']);
        });

        Schema::create('sucursales_productos', function (Blueprint $tabla) {
            $tabla->id('id_sucursal_producto');
            $tabla->foreignId('ref_sucursal')->constrained('sucursales', 'id_sucursal')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_producto')->constrained('productos', 'id_producto')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->boolean('habilitado')->default(true);
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_sucursal', 'ref_producto']);
        });

        Schema::create('sucursales_productos_areas_preparacion', function (Blueprint $tabla) {
            $tabla->id('id_sucursal_producto_area_preparacion');
            $tabla->foreignId('ref_sucursal_producto');
            $tabla->foreign('ref_sucursal_producto', 'spap_suc_prod_fk')->references('id_sucursal_producto')->on('sucursales_productos')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_area_preparacion');
            $tabla->foreign('ref_area_preparacion', 'spap_area_prep_fk')->references('id_area_preparacion')->on('areas_preparacion')->cascadeOnUpdate()->restrictOnDelete();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_sucursal_producto', 'ref_area_preparacion'], 'spap_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales_productos_areas_preparacion');
        Schema::dropIfExists('sucursales_productos');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('areas_preparacion');
        Schema::dropIfExists('sucursales_categorias_productos');
        Schema::dropIfExists('categorias_productos');
    }
};
