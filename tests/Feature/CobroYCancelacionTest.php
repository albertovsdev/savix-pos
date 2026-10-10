<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CobroYCancelacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelar_un_producto_enviado_con_pin_lo_registra_como_merma(): void
    {
        [, $id_sucursal, $id_pedido, , $detalles] = $this->crearPedidoEnviadoConDosProductos();
        $id_detalle = $detalles->first()->id_detalle_ronda_pedido;

        $this->post("/operacion/detalles/{$id_detalle}/cancelar", [
            'ref_sucursal' => $id_sucursal,
            'motivo' => 'El cliente canceló antes de recibirlo.',
            'nombre_usuario_autorizacion' => 'admin.medel',
            'pin_autorizacion' => '1234',
            'fue_preparado' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('detalles_rondas_pedidos', [
            'id_detalle_ronda_pedido' => $id_detalle,
            'estado' => 'cancelado',
            'activo' => false,
        ]);
        $this->assertDatabaseHas('cancelaciones_detalles_pedidos', [
            'ref_detalle_ronda_pedido' => $id_detalle,
            'fue_preparado' => true,
        ]);
        $this->assertDatabaseHas('mermas_pedidos', ['ref_sucursal' => $id_sucursal]);
        $this->assertDatabaseHas('detalles_rondas_pedidos_areas_preparacion', [
            'ref_detalle_ronda_pedido' => $id_detalle,
            'estado_preparacion' => 'cancelado',
        ]);
        $this->assertDatabaseHas('pedidos', ['id_pedido' => $id_pedido, 'estado' => 'abierto']);
    }

    public function test_divide_cuenta_cobra_con_pago_mixto_y_cierra_el_pedido_al_pagar_todas_las_partes(): void
    {
        [, $id_sucursal, $id_pedido, $id_mesa, $detalles] = $this->crearPedidoEnviadoConDosProductos();

        $this->post("/caja/pedidos/{$id_pedido}/partes", [
            'ref_sucursal' => $id_sucursal,
            'nombre' => 'Persona 2',
        ])->assertSessionHasNoErrors();
        $partes = DB::table('partes_cuenta_pedidos')->where('ref_pedido', $id_pedido)->orderBy('orden')->get();
        $parte_general = $partes->first();
        $parte_dos = $partes->last();

        $this->put("/caja/detalles/{$detalles->last()->id_detalle_ronda_pedido}/parte", [
            'ref_sucursal' => $id_sucursal,
            'ref_parte_cuenta_pedido' => $parte_dos->id_parte_cuenta_pedido,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('detalles_rondas_pedidos', [
            'id_detalle_ronda_pedido' => $detalles->last()->id_detalle_ronda_pedido,
            'ref_parte_cuenta_pedido' => $parte_dos->id_parte_cuenta_pedido,
        ]);

        $this->post("/caja/partes/{$parte_general->id_parte_cuenta_pedido}/cobrar", [
            'ref_sucursal' => $id_sucursal,
            'pagos' => [['tipo_pago' => 'efectivo', 'importe' => '100.00']],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('partes_cuenta_pedidos', ['id_parte_cuenta_pedido' => $parte_general->id_parte_cuenta_pedido, 'estado' => 'pagada']);
        $this->assertDatabaseHas('pedidos', ['id_pedido' => $id_pedido, 'estado' => 'abierto']);

        $this->post("/caja/partes/{$parte_dos->id_parte_cuenta_pedido}/cobrar", [
            'ref_sucursal' => $id_sucursal,
            'pagos' => [
                ['tipo_pago' => 'efectivo', 'importe' => '40.00'],
                ['tipo_pago' => 'tarjeta', 'importe' => '60.00'],
            ],
        ])->assertRedirect("/tickets/pedidos/{$id_pedido}");

        $this->assertDatabaseHas('pedidos', ['id_pedido' => $id_pedido, 'estado' => 'cerrado', 'folio_ticket' => 'SX-SM-0001']);
        $this->assertDatabaseHas('mesas', ['id_mesa' => $id_mesa, 'estado' => 'libre']);
        $this->assertDatabaseCount('pagos_partes_cuenta_pedidos', 3);
        $this->get("/tickets/pedidos/{$id_pedido}")->assertOk()->assertSee('SX-SM-0001')->assertSee('Persona 2');
    }

    public function test_pin_incorrecto_devuelve_error_de_formulario_y_no_cancela_el_producto(): void
    {
        [, $id_sucursal, , , $detalles] = $this->crearPedidoEnviadoConDosProductos();
        $id_detalle = $detalles->first()->id_detalle_ronda_pedido;

        $this->post("/operacion/detalles/{$id_detalle}/cancelar", [
            'ref_sucursal' => $id_sucursal,
            'motivo' => 'Prueba de autorización.',
            'nombre_usuario_autorizacion' => 'admin.medel',
            'pin_autorizacion' => '0000',
            'fue_preparado' => false,
        ])->assertSessionHasErrors('pin_autorizacion');

        $this->assertDatabaseHas('detalles_rondas_pedidos', ['id_detalle_ronda_pedido' => $id_detalle, 'estado' => 'activo']);
        $this->assertDatabaseMissing('cancelaciones_detalles_pedidos', ['ref_detalle_ronda_pedido' => $id_detalle]);
    }

    public function test_pago_con_importe_distinto_devuelve_error_de_formulario(): void
    {
        [, $id_sucursal, $id_pedido] = $this->crearPedidoEnviadoConDosProductos();
        $id_parte = DB::table('partes_cuenta_pedidos')->where('ref_pedido', $id_pedido)->value('id_parte_cuenta_pedido');

        $this->post("/caja/partes/{$id_parte}/cobrar", [
            'ref_sucursal' => $id_sucursal,
            'pagos' => [
                ['tipo_pago' => 'tarjeta', 'importe' => '10.00'],
                ['tipo_pago' => 'tarjeta', 'importe' => '50.00'],
                ['tipo_pago' => 'tarjeta', 'importe' => '60.00'],
            ],
        ])->assertSessionHasErrors('pagos');

        $this->assertDatabaseHas('partes_cuenta_pedidos', ['id_parte_cuenta_pedido' => $id_parte, 'estado' => 'abierta']);
    }

    public function test_cierra_la_cuenta_si_la_parte_general_queda_sin_productos(): void
    {
        [, $id_sucursal, $id_pedido, $id_mesa, $detalles] = $this->crearPedidoEnviadoConDosProductos();
        $this->post("/caja/pedidos/{$id_pedido}/partes", ['ref_sucursal' => $id_sucursal, 'nombre' => 'Ana'])->assertSessionHasNoErrors();
        $partes = DB::table('partes_cuenta_pedidos')->where('ref_pedido', $id_pedido)->orderBy('orden')->get();
        $parte_general = $partes->first();
        $parte_ana = $partes->last();

        foreach ($detalles as $detalle) {
            $this->put("/caja/detalles/{$detalle->id_detalle_ronda_pedido}/parte", [
                'ref_sucursal' => $id_sucursal,
                'ref_parte_cuenta_pedido' => $parte_ana->id_parte_cuenta_pedido,
            ])->assertSessionHasNoErrors();
        }

        $this->post("/caja/partes/{$parte_ana->id_parte_cuenta_pedido}/cobrar", [
            'ref_sucursal' => $id_sucursal,
            'pagos' => [['tipo_pago' => 'efectivo', 'importe' => '200.00']],
        ])->assertRedirect("/tickets/pedidos/{$id_pedido}");

        $this->assertDatabaseHas('partes_cuenta_pedidos', ['id_parte_cuenta_pedido' => $parte_general->id_parte_cuenta_pedido, 'estado' => 'pagada']);
        $this->assertDatabaseHas('pedidos', ['id_pedido' => $id_pedido, 'estado' => 'cerrado']);
        $this->assertDatabaseHas('mesas', ['id_mesa' => $id_mesa, 'estado' => 'libre']);
    }

    public function test_el_ticket_usa_ruta_relativa_para_el_logo_del_negocio(): void
    {
        [$usuario, , $id_pedido] = $this->crearPedidoEnviadoConDosProductos();
        DB::table('negocios')->update(['ruta_logo' => 'logos/logo-prueba.png']);
        DB::table('pedidos')->where('id_pedido', $id_pedido)->update([
            'estado' => 'cerrado',
            'folio_ticket' => 'SX-SM-0099',
            'cerrado_en' => now(),
        ]);

        $this->actingAs($usuario)
            ->get("/tickets/pedidos/{$id_pedido}")
            ->assertOk()
            ->assertSee('src="/storage/logos/logo-prueba.png"', false);
    }

    private function crearPedidoEnviadoConDosProductos(): array
    {
        $usuario = $this->configurarInstalacion();
        $this->actingAs($usuario);
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
        foreach (range(1, 2) as $numero) {
            $this->post("/operacion/pedidos/{$id_pedido}/detalles", [
                'ref_producto' => $id_producto, 'cantidad' => '1', 'nota_preparacion' => "Producto {$numero}",
            ])->assertSessionHasNoErrors();
        }
        $this->post("/operacion/pedidos/{$id_pedido}/confirmar-ronda")->assertSessionHasNoErrors();
        $detalles = DB::table('detalles_rondas_pedidos')
            ->join('rondas_pedidos', 'rondas_pedidos.id_ronda_pedido', '=', 'detalles_rondas_pedidos.ref_ronda_pedido')
            ->where('rondas_pedidos.ref_pedido', $id_pedido)
            ->orderBy('detalles_rondas_pedidos.id_detalle_ronda_pedido')
            ->select('detalles_rondas_pedidos.*')
            ->get();

        $this->assertNotNull($detalles->first()->ref_parte_cuenta_pedido);

        return [$usuario, $id_sucursal, $id_pedido, $id_mesa, $detalles];
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
