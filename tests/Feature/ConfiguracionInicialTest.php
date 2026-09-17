<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\ServicioAutorizacionPin;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracionInicialTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuracion_inicial_crea_el_negocio_y_acceso_administrador(): void
    {
        $this->seed(DatabaseSeeder::class);

        $respuesta = $this->post('/configuracion-inicial', [
            'nombre_negocio' => 'Restaurante Bar MEDEL',
            'nombre_comercial' => 'MEDEL',
            'clave_folio' => 'SX',
            'nombre_sucursal' => 'Matriz',
            'clave_sucursal' => 'SM',
            'nombre_admin' => 'Administrador MEDEL',
            'nombre_usuario' => 'admin.medel',
            'correo' => 'admin@medel.test',
            'contrasena' => 'Contrasena-segura-123',
            'contrasena_confirmation' => 'Contrasena-segura-123',
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ]);

        $respuesta->assertRedirect('/panel');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('negocios', ['nombre_comercial' => 'MEDEL']);
        $this->assertDatabaseHas('sucursales', ['clave' => 'SM', 'nombre' => 'Matriz']);
        $this->assertDatabaseHas('usuarios', ['nombre_usuario' => 'admin.medel']);
        $this->assertDatabaseCount('sucursales_modulos', 10);

        $usuario = Usuario::query()->where('nombre_usuario', 'admin.medel')->firstOrFail();
        $this->assertTrue(app(ServicioAutorizacionPin::class)->esValido($usuario, '1234'));
    }

    public function test_inicio_de_sesion_usa_nombre_usuario_y_contrasena(): void
    {
        $this->seed(DatabaseSeeder::class);
        $usuario = Usuario::factory()->create([
            'nombre_usuario' => 'caja.medel',
            'contrasena' => 'Contrasena-segura-123',
        ]);

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
        ]);

        $this->delete('/acceso');

        $this->post('/acceso', [
            'nombre_usuario' => $usuario->nombre_usuario,
            'contrasena' => 'Contrasena-segura-123',
        ])->assertRedirect('/panel');

        $this->assertAuthenticatedAs($usuario);
    }
}
