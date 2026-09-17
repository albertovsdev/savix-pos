<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosInicialesSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        $roles = [
            ['codigo' => 'dev', 'nombre' => 'Desarrollador', 'descripcion' => 'Acceso técnico total de SAVIX INDUSTRIES.'],
            ['codigo' => 'admin', 'nombre' => 'Administrador', 'descripcion' => 'Administración completa del negocio.'],
            ['codigo' => 'gerente', 'nombre' => 'Gerente', 'descripcion' => 'Supervisión operativa con permisos configurables.'],
            ['codigo' => 'caja', 'nombre' => 'Caja', 'descripcion' => 'Cobros, apertura y corte de caja.'],
            ['codigo' => 'mesero', 'nombre' => 'Mesero', 'descripcion' => 'Toma y seguimiento de pedidos.'],
            ['codigo' => 'cocina', 'nombre' => 'Cocina', 'descripcion' => 'Preparación de pedidos de cocina.'],
            ['codigo' => 'barra', 'nombre' => 'Barra', 'descripcion' => 'Preparación de bebidas.'],
            ['codigo' => 'inventario', 'nombre' => 'Inventario', 'descripcion' => 'Control de existencias y ajustes.'],
        ];

        foreach ($roles as &$rol) {
            $rol['es_sistema'] = true;
            $rol['activo'] = true;
            $rol['creado_en'] = $ahora;
            $rol['actualizado_en'] = $ahora;
        }
        unset($rol);

        DB::table('cat_roles')->upsert($roles, ['codigo'], ['nombre', 'descripcion', 'es_sistema', 'activo', 'actualizado_en']);

        $permisos = [
            ['codigo' => 'negocio.ver', 'modulo' => 'negocio', 'nombre' => 'Ver configuración de negocio'],
            ['codigo' => 'negocio.configurar', 'modulo' => 'negocio', 'nombre' => 'Configurar negocio'],
            ['codigo' => 'sucursales.ver', 'modulo' => 'sucursales', 'nombre' => 'Ver sucursales'],
            ['codigo' => 'sucursales.gestionar', 'modulo' => 'sucursales', 'nombre' => 'Gestionar sucursales'],
            ['codigo' => 'usuarios.ver', 'modulo' => 'usuarios', 'nombre' => 'Ver usuarios'],
            ['codigo' => 'usuarios.gestionar', 'modulo' => 'usuarios', 'nombre' => 'Gestionar usuarios'],
            ['codigo' => 'roles.ver', 'modulo' => 'roles', 'nombre' => 'Ver roles y permisos'],
            ['codigo' => 'roles.gestionar', 'modulo' => 'roles', 'nombre' => 'Gestionar roles y permisos'],
            ['codigo' => 'modulos.configurar', 'modulo' => 'modulos', 'nombre' => 'Configurar módulos por sucursal'],
            ['codigo' => 'catalogos.ver', 'modulo' => 'catalogos', 'nombre' => 'Ver catálogos'],
            ['codigo' => 'catalogos.gestionar', 'modulo' => 'catalogos', 'nombre' => 'Gestionar catálogos'],
            ['codigo' => 'ventas.ver', 'modulo' => 'ventas', 'nombre' => 'Ver ventas'],
            ['codigo' => 'ventas.crear', 'modulo' => 'ventas', 'nombre' => 'Crear pedidos'],
            ['codigo' => 'ventas.enviar_preparacion', 'modulo' => 'ventas', 'nombre' => 'Enviar pedidos a preparación'],
            ['codigo' => 'ventas.cancelar', 'modulo' => 'ventas', 'nombre' => 'Cancelar pedidos'],
            ['codigo' => 'ventas.reabrir', 'modulo' => 'ventas', 'nombre' => 'Reabrir ventas'],
            ['codigo' => 'ventas.aplicar_descuento', 'modulo' => 'ventas', 'nombre' => 'Aplicar descuentos'],
            ['codigo' => 'preparacion.ver', 'modulo' => 'preparacion', 'nombre' => 'Ver preparación'],
            ['codigo' => 'preparacion.actualizar_estado', 'modulo' => 'preparacion', 'nombre' => 'Actualizar preparación'],
            ['codigo' => 'caja.cobrar', 'modulo' => 'caja', 'nombre' => 'Cobrar ventas'],
            ['codigo' => 'caja.realizar_corte', 'modulo' => 'caja', 'nombre' => 'Realizar corte de caja'],
            ['codigo' => 'inventario.ver', 'modulo' => 'inventario', 'nombre' => 'Ver inventario'],
            ['codigo' => 'inventario.ajustar', 'modulo' => 'inventario', 'nombre' => 'Ajustar inventario'],
            ['codigo' => 'reportes.ver', 'modulo' => 'reportes', 'nombre' => 'Ver reportes'],
            ['codigo' => 'auditoria.ver', 'modulo' => 'auditoria', 'nombre' => 'Ver bitácora de auditoría'],
        ];

        foreach ($permisos as &$permiso) {
            $permiso['descripcion'] = null;
            $permiso['activo'] = true;
            $permiso['creado_en'] = $ahora;
            $permiso['actualizado_en'] = $ahora;
        }
        unset($permiso);

        DB::table('cat_permisos')->upsert($permisos, ['codigo'], ['modulo', 'nombre', 'descripcion', 'activo', 'actualizado_en']);

        $modulos = [
            ['codigo' => 'mesas', 'nombre' => 'Mesas', 'orden' => 10],
            ['codigo' => 'mostrador', 'nombre' => 'Mostrador', 'orden' => 20],
            ['codigo' => 'meseros', 'nombre' => 'Meseros', 'orden' => 30],
            ['codigo' => 'cocina', 'nombre' => 'Cocina', 'orden' => 40],
            ['codigo' => 'barra', 'nombre' => 'Barra', 'orden' => 50],
            ['codigo' => 'inventario', 'nombre' => 'Inventario', 'orden' => 60],
            ['codigo' => 'division_cuenta', 'nombre' => 'División de cuenta', 'orden' => 70],
            ['codigo' => 'propinas', 'nombre' => 'Propinas', 'orden' => 80],
            ['codigo' => 'descuentos', 'nombre' => 'Descuentos', 'orden' => 90],
            ['codigo' => 'impresion_tickets', 'nombre' => 'Impresión de tickets', 'orden' => 100],
        ];

        foreach ($modulos as &$modulo) {
            $modulo['descripcion'] = null;
            $modulo['activo'] = true;
            $modulo['creado_en'] = $ahora;
            $modulo['actualizado_en'] = $ahora;
        }
        unset($modulo);

        DB::table('cat_modulos')->upsert($modulos, ['codigo'], ['nombre', 'descripcion', 'orden', 'activo', 'actualizado_en']);
    }
}
