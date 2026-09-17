<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdministracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_configura_negocio_sucursal_y_usuario(): void
    {
        $administrador = $this->configurarInstalacion();

        $this->get('/administracion')->assertOk();

        $this->put('/administracion/negocio', [
            'nombre' => 'Restaurante Bar MEDEL',
            'nombre_comercial' => 'MEDEL Mariscos',
            'clave_folio' => 'SX',
            'rfc' => 'XAXX010101000',
            'correo' => 'contacto@medel.test',
            'telefono' => '2221234567',
            'color_primario' => '#5146D8',
            'color_secundario' => '#171722',
            'color_acento' => '#9A93DD',
            'tema_predeterminado' => 'claro',
            'porcentaje_iva' => 16,
            'precios_incluyen_iva' => true,
            'ancho_ticket_mm' => 80,
            'direccion_ticket' => 'Puebla, México',
            'pie_ticket' => 'Gracias por su visita',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('negocios', ['nombre_comercial' => 'MEDEL Mariscos', 'tema_predeterminado' => 'claro']);

        $modulos = DB::table('cat_modulos')->pluck('id_modulo')->map(fn ($id) => (int) $id)->all();

        $this->post('/administracion/sucursales', [
            'clave' => 'SN',
            'nombre' => 'Sucursal Norte',
            'telefono' => null,
            'correo' => null,
            'direccion' => 'Avenida Norte 20',
            'modulos' => $modulos,
        ])->assertSessionHasNoErrors();

        $id_sucursal = DB::table('sucursales')->where('clave', 'SN')->value('id_sucursal');
        $id_rol = DB::table('cat_roles')->where('codigo', 'mesero')->value('id_rol');

        $this->post('/administracion/usuarios', [
            'nombre' => 'Mesero MEDEL',
            'nombre_usuario' => 'mesero.medel',
            'correo' => null,
            'contrasena' => 'Contrasena-segura-123',
            'contrasena_confirmation' => 'Contrasena-segura-123',
            'pin' => '4321',
            'pin_confirmation' => '4321',
            'ref_sucursal' => $id_sucursal,
            'ref_rol' => $id_rol,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('usuarios', ['nombre_usuario' => 'mesero.medel']);
        $this->assertDatabaseHas('usuarios_roles', ['ref_rol' => $id_rol, 'ref_sucursal' => $id_sucursal]);
        $this->assertDatabaseHas('bitacora_auditoria', ['ref_usuario' => $administrador->id_usuario, 'accion' => 'crear_usuario']);
    }

    public function test_usuario_sin_permiso_no_accede_a_administracion(): void
    {
        $this->configurarInstalacion();
        $sin_permiso = Usuario::factory()->create();
        $id_sucursal = DB::table('sucursales')->value('id_sucursal');

        DB::table('usuarios_sucursales')->insert([
            'ref_usuario' => $sin_permiso->id_usuario,
            'ref_sucursal' => $id_sucursal,
            'es_principal' => true,
            'activo' => true,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $this->actingAs($sin_permiso)->get('/administracion')->assertForbidden();
    }

    private function configurarInstalacion(): Usuario
    {
        $this->seed(DatabaseSeeder::class);

        $this->post('/configuracion-inicial', [
            'nombre_negocio' => 'Restaurante Bar MEDEL',
            'nombre_comercial' => 'MEDEL',
            'clave_folio' => 'SX',
            'nombre_sucursal' => 'Matriz',
            'clave_sucursal' => 'SM',
            'nombre_admin' => 'Administrador MEDEL',
            'nombre_usuario' => 'admin.medel',
            'correo' => null,
            'contrasena' => 'Contrasena-segura-123',
            'contrasena_confirmation' => 'Contrasena-segura-123',
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ])->assertRedirect('/panel');

        return Usuario::query()->where('nombre_usuario', 'admin.medel')->firstOrFail();
    }
}
