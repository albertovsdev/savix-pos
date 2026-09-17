<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $tabla) {
            $tabla->id('id_usuario');
            $tabla->string('nombre', 120);
            $tabla->string('nombre_usuario', 80)->unique();
            $tabla->string('correo', 191)->nullable()->unique();
            $tabla->timestamp('correo_verificado_en')->nullable();
            $tabla->string('contrasena');
            $tabla->string('pin_hash')->nullable();
            $tabla->string('token_recordar', 100)->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestamp('creado_en')->useCurrent();
            $tabla->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
