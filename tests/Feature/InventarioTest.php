<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajusta_existencia_de_un_producto_y_registra_el_movimiento(): void
    {
        $usuario = $this->configurarInstalacion();
        $this->actingAs($usuario);
        $id_sucursal = DB::table('sucursales')->value('id_sucursal');

        $this->post('/catalogos/productos', [
            'ref_sucursal' => $id_sucursal,
            'nombre' => 'Cerveza clara',
            'precio_compra' => '30.00',
            'precio_venta' => '70.00',
            'tipo_inventario' => 'unidad',
            'existencia_actual' => '5.0000',
            'existencia_minima' => '2.0000',
            'areas_preparacion' => [],
        ])->assertSessionHasNoErrors();
        $id_producto = DB::table('productos')->where('nombre', 'Cerveza clara')->value('id_producto');

        $this->post("/inventario/productos/{$id_producto}/ajustar", [
            'ref_sucursal' => $id_sucursal,
            'existencia_nueva' => '12.5000',
            'motivo' => 'Conteo físico de cierre.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sucursales_productos', [
            'ref_sucursal' => $id_sucursal,
            'ref_producto' => $id_producto,
            'existencia_actual' => 12.5,
        ]);
        $this->assertDatabaseHas('movimientos_inventario', [
            'ref_sucursal' => $id_sucursal,
            'ref_producto' => $id_producto,
            'tipo_movimiento' => 'entrada_ajuste',
            'cantidad' => 7.5,
            'existencia_anterior' => 5,
            'existencia_posterior' => 12.5,
            'descripcion' => 'Ajuste manual: Conteo físico de cierre.',
        ]);
        $this->assertDatabaseHas('bitacora_auditoria', [
            'ref_usuario' => $usuario->id_usuario,
            'modulo' => 'inventario',
            'accion' => 'ajustar_existencia_producto',
        ]);
    }

    private function configurarInstalacion(): Usuario
    {
        $this->seed(DatabaseSeeder::class);
        $this->post('/configuracion-inicial', [
            'nombre_negocio' => 'Restaurante Bar MEDEL', 'nombre_comercial' => 'MEDEL', 'clave_folio' => 'SX',
            'nombre_sucursal' => 'Matriz', 'clave_sucursal' => 'SM', 'nombre_admin' => 'Administrador MEDEL',
            'nombre_usuario' => 'admin.medel', 'correo' => null, 'contrasena' => 'Contrasena-segura-123',
            'contrasena_confirmation' => 'Contrasena-segura-123', 'pin' => '1234', 'pin_confirmation' => '1234',
        ])->assertRedirect('/panel');

        return Usuario::query()->where('nombre_usuario', 'admin.medel')->firstOrFail();
    }
}
