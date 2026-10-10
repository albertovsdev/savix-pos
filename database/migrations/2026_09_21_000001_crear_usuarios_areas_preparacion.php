<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_areas_preparacion', function (Blueprint $tabla) {
            $tabla->id('id_usuario_area_preparacion');
            $tabla->foreignId('ref_usuario');
            $tabla->foreign('ref_usuario', 'uap_usuario_fk')->references('id_usuario')->on('usuarios')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->foreignId('ref_area_preparacion');
            $tabla->foreign('ref_area_preparacion', 'uap_area_fk')->references('id_area_preparacion')->on('areas_preparacion')->cascadeOnUpdate()->cascadeOnDelete();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $tabla->unique(['ref_usuario', 'ref_area_preparacion'], 'usuario_area_preparacion_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_areas_preparacion');
    }
};
