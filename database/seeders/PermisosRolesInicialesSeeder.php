<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosRolesInicialesSeeder extends Seeder
{
    public function run(): void
    {
        $roles_por_codigo = DB::table('cat_roles')->pluck('id_rol', 'codigo');
        $permisos_por_codigo = DB::table('cat_permisos')->pluck('id_permiso', 'codigo');
        $todos_los_permisos = array_keys($permisos_por_codigo->all());

        $permisos_por_rol = [
            'dev' => $todos_los_permisos,
            'admin' => $todos_los_permisos,
            'gerente' => [
                'usuarios.ver', 'ventas.ver', 'ventas.crear', 'ventas.enviar_preparacion',
                'ventas.cancelar', 'ventas.reabrir', 'ventas.aplicar_descuento',
                'preparacion.ver', 'preparacion.actualizar_estado', 'caja.cobrar',
                'caja.realizar_corte', 'inventario.ver', 'reportes.ver', 'auditoria.ver',
            ],
            'caja' => ['ventas.ver', 'caja.cobrar', 'caja.realizar_corte'],
            'mesero' => ['ventas.ver', 'ventas.crear', 'ventas.enviar_preparacion'],
            'cocina' => ['preparacion.ver', 'preparacion.actualizar_estado'],
            'barra' => ['preparacion.ver', 'preparacion.actualizar_estado'],
            'inventario' => ['inventario.ver', 'inventario.ajustar'],
        ];

        $ahora = now();
        $asignaciones = [];

        foreach ($permisos_por_rol as $codigo_rol => $codigos_permisos) {
            foreach ($codigos_permisos as $codigo_permiso) {
                if (! isset($roles_por_codigo[$codigo_rol], $permisos_por_codigo[$codigo_permiso])) {
                    continue;
                }

                $asignaciones[] = [
                    'ref_rol' => $roles_por_codigo[$codigo_rol],
                    'ref_permiso' => $permisos_por_codigo[$codigo_permiso],
                    'activo' => true,
                    'creado_en' => $ahora,
                    'actualizado_en' => $ahora,
                ];
            }
        }

        DB::table('roles_permisos')->upsert(
            $asignaciones,
            ['ref_rol', 'ref_permiso'],
            ['activo', 'actualizado_en'],
        );
    }
}
