<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class ServicioPermisos
{
    public function tiene(Usuario $usuario, string $codigo_permiso, ?int $id_sucursal = null): bool
    {
        $asignaciones = DB::table('usuarios_permisos')
            ->join('cat_permisos', 'cat_permisos.id_permiso', '=', 'usuarios_permisos.ref_permiso')
            ->where('usuarios_permisos.ref_usuario', $usuario->id_usuario)
            ->where('cat_permisos.codigo', $codigo_permiso)
            ->where('usuarios_permisos.activo', true)
            ->where(function ($consulta) use ($id_sucursal): void {
                $consulta->whereNull('usuarios_permisos.ref_sucursal');

                if ($id_sucursal !== null) {
                    $consulta->orWhere('usuarios_permisos.ref_sucursal', $id_sucursal);
                }
            })
            ->pluck('usuarios_permisos.tipo_asignacion');

        if ($asignaciones->contains('denegar')) {
            return false;
        }

        if ($asignaciones->contains('permitir')) {
            return true;
        }

        return DB::table('usuarios_roles')
            ->join('roles_permisos', 'roles_permisos.ref_rol', '=', 'usuarios_roles.ref_rol')
            ->join('cat_permisos', 'cat_permisos.id_permiso', '=', 'roles_permisos.ref_permiso')
            ->where('usuarios_roles.ref_usuario', $usuario->id_usuario)
            ->where('usuarios_roles.activo', true)
            ->where('roles_permisos.activo', true)
            ->where('cat_permisos.codigo', $codigo_permiso)
            ->where(function ($consulta) use ($id_sucursal): void {
                $consulta->whereNull('usuarios_roles.ref_sucursal');

                if ($id_sucursal !== null) {
                    $consulta->orWhere('usuarios_roles.ref_sucursal', $id_sucursal);
                }
            })
            ->exists();
    }
}
