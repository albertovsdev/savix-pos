<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_cache', function (Blueprint $tabla) {
            $tabla->string('key')->primary();
            $tabla->mediumText('value');
            $tabla->integer('expiration')->index();
        });

        Schema::create('bloqueos_cache', function (Blueprint $tabla) {
            $tabla->string('key')->primary();
            $tabla->string('owner');
            $tabla->integer('expiration')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_cache');
        Schema::dropIfExists('almacen_cache');
    }
};
