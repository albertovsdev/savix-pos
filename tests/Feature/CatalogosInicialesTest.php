<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogosInicialesTest extends TestCase
{
    use RefreshDatabase;

    public function test_siembra_roles_permisos_modulos_y_asignaciones_iniciales(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('cat_roles', [
            'codigo' => 'dev',
            'nombre' => 'Desarrollador',
        ]);

        $this->assertDatabaseHas('cat_permisos', [
            'codigo' => 'ventas.cancelar',
        ]);

        $this->assertDatabaseHas('cat_modulos', [
            'codigo' => 'division_cuenta',
        ]);

        $id_rol_dev = DB::table('cat_roles')->where('codigo', 'dev')->value('id_rol');
        $id_permiso_cancelar = DB::table('cat_permisos')->where('codigo', 'ventas.cancelar')->value('id_permiso');

        $this->assertDatabaseHas('roles_permisos', [
            'ref_rol' => $id_rol_dev,
            'ref_permiso' => $id_permiso_cancelar,
            'activo' => true,
        ]);
    }

    public function test_usuario_usa_tabla_y_campos_en_espanol(): void
    {
        $usuario = Usuario::factory()->create();

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $usuario->id_usuario,
            'correo' => $usuario->correo,
        ]);
    }
}
