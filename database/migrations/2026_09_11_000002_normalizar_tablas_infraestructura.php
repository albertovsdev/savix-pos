<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = [
            'cache' => 'almacen_cache',
            'cache_locks' => 'bloqueos_cache',
            'jobs' => 'tareas_cola',
            'job_batches' => 'lotes_tareas_cola',
            'failed_jobs' => 'tareas_fallidas',
        ];

        foreach ($tablas as $origen => $destino) {
            if (Schema::hasTable($origen) && ! Schema::hasTable($destino)) {
                Schema::rename($origen, $destino);
            }
        }
    }

    public function down(): void
    {
        $tablas = [
            'almacen_cache' => 'cache',
            'bloqueos_cache' => 'cache_locks',
            'tareas_cola' => 'jobs',
            'lotes_tareas_cola' => 'job_batches',
            'tareas_fallidas' => 'failed_jobs',
        ];

        foreach ($tablas as $origen => $destino) {
            if (Schema::hasTable($origen) && ! Schema::hasTable($destino)) {
                Schema::rename($origen, $destino);
            }
        }
    }
};
