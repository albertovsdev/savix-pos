<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_abre_mesa_y_manda_rondas_independientes_a_preparacion(): void
    {
        $this->configurarInstalacion();
        $id_sucursal = DB::table('sucursales')->value('id_sucursal');

        $this->post('/catalogos/categorias', ['nombre' => 'Bebidas', 'orden' => 10])->assertSessionHasNoErrors();
        $id_categoria = DB::table('categorias_productos')->where('nombre', 'Bebidas')->value('id_categoria_producto');
        $this->post('/catalogos/areas-preparacion', [
            'ref_sucursal' => $id_sucursal, 'nombre' => 'Barra', 'codigo' => 'BARRA', 'orden' => 10,
        ])->assertSessionHasNoErrors();
        $id_area = DB::table('areas_preparacion')->where('codigo', 'BARRA')->value('id_area_preparacion');
        $this->post('/catalogos/productos', [
            'ref_sucursal' => $id_sucursal, 'ref_categoria_producto' => $id_categoria, 'nombre' => 'Michelada',
            'precio_compra' => '40.00', 'precio_venta' => '100.00', 'tipo_inventario' => 'sin_control',
            'areas_preparacion' => [$id_area],
        ])->assertSessionHasNoErrors();
        $id_producto = DB::table('productos')->where('nombre', 'Michelada')->value('id_producto');

        $this->post('/operacion/mesas', ['nombre' => 'Mesa 1', 'orden' => 1])->assertSessionHasNoErrors();
        $id_mesa = DB::table('mesas')->where('nombre', 'Mesa 1')->value('id_mesa');
        $this->post("/operacion/mesas/{$id_mesa}/abrir")->assertRedirect();
        $id_pedido = DB::table('pedidos')->where('ref_mesa', $id_mesa)->value('id_pedido');

        $this->get("/operacion?ref_pedido={$id_pedido}")->assertInertia(fn (Assert $pagina) => $pagina
            ->component('Operacion', false)
            ->where('pedido.id_pedido', $id_pedido)
            ->where('pedido.ronda_borrador.numero_ronda', 1)
        );
        $this->post("/operacion/pedidos/{$id_pedido}/detalles", [
            'ref_producto' => $id_producto, 'cantidad' => '2', 'nota_preparacion' => 'Sin picante',
        ])->assertSessionHasNoErrors();
        $this->post("/operacion/pedidos/{$id_pedido}/confirmar-ronda")->assertSessionHasNoErrors();

        $id_ronda_uno = DB::table('rondas_pedidos')->where('ref_pedido', $id_pedido)->where('numero_ronda', 1)->value('id_ronda_pedido');
        $id_ronda_dos = DB::table('rondas_pedidos')->where('ref_pedido', $id_pedido)->where('numero_ronda', 2)->value('id_ronda_pedido');
        $this->assertDatabaseHas('rondas_pedidos', ['id_ronda_pedido' => $id_ronda_uno, 'estado' => 'enviada', 'importe_total' => 200]);
        $this->assertDatabaseHas('rondas_pedidos', ['id_ronda_pedido' => $id_ronda_dos, 'estado' => 'borrador', 'importe_total' => 0]);
        $this->assertDatabaseCount('detalles_rondas_pedidos', 1);
        $this->assertDatabaseHas('detalles_rondas_pedidos_areas_preparacion', ['ref_area_preparacion' => $id_area, 'estado_preparacion' => 'pendiente']);
        $this->assertDatabaseHas('mesas', ['id_mesa' => $id_mesa, 'estado' => 'ocupada']);

        $this->post("/operacion/pedidos/{$id_pedido}/detalles", [
            'ref_producto' => $id_producto, 'cantidad' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('detalles_rondas_pedidos', ['ref_ronda_pedido' => $id_ronda_dos, 'cantidad' => 1]);
        $this->assertDatabaseCount('detalles_rondas_pedidos', 2);
    }

    public function test_abre_un_pedido_de_mostrador_sin_mesa(): void
    {
        $this->configurarInstalacion();

        $this->post('/operacion/mostrador')->assertRedirect();

        $this->assertDatabaseHas('pedidos', ['tipo_servicio' => 'mostrador', 'estado' => 'abierto', 'referencia_mostrador' => 'Mostrador']);
        $id_pedido = DB::table('pedidos')->where('tipo_servicio', 'mostrador')->value('id_pedido');
        $this->assertDatabaseHas('rondas_pedidos', ['ref_pedido' => $id_pedido, 'numero_ronda' => 1, 'estado' => 'borrador']);
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
