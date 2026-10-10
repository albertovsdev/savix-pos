<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComprasYReportesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_proveedor_y_compra_que_incrementa_existencias(): void
    {
        $usuario = $this->configurarInstalacion();
        $this->actingAs($usuario);
        $id_sucursal = DB::table('sucursales')->value('id_sucursal');
        $id_unidad = DB::table('cat_unidades_medida')->where('codigo', 'pieza')->value('id_unidad_medida');

        $this->post('/catalogos/productos', [
            'ref_sucursal' => $id_sucursal, 'nombre' => 'Cerveza clara', 'precio_compra' => '30.00', 'precio_venta' => '70.00',
            'tipo_inventario' => 'unidad', 'existencia_actual' => '2', 'existencia_minima' => '1', 'areas_preparacion' => [],
        ])->assertSessionHasNoErrors();
        $this->post('/catalogos/insumos', [
            'ref_sucursal' => $id_sucursal, 'nombre' => 'Limón', 'ref_unidad_medida' => $id_unidad,
            'costo_unitario' => '2.00', 'existencia_actual' => '4', 'existencia_minima' => '1',
        ])->assertSessionHasNoErrors();
        $id_producto = DB::table('productos')->where('nombre', 'Cerveza clara')->value('id_producto');
        $id_insumo = DB::table('insumos')->where('nombre', 'Limón')->value('id_insumo');

        $this->post('/compras/proveedores', [
            'nombre' => 'Distribuidora del Centro', 'telefono' => '2221234567', 'correo' => 'ventas@centro.test',
        ])->assertSessionHasNoErrors();
        $id_proveedor = DB::table('proveedores')->where('nombre', 'Distribuidora del Centro')->value('id_proveedor');

        $this->post('/compras', [
            'ref_sucursal' => $id_sucursal, 'ref_proveedor' => $id_proveedor, 'fecha_compra' => now()->toDateString(),
            'folio_proveedor' => 'FC-1001', 'notas' => 'Entrega de prueba.',
            'detalles' => [
                ['tipo_recurso' => 'producto', 'ref_recurso' => $id_producto, 'cantidad' => '6', 'costo_unitario' => '31.50'],
                ['tipo_recurso' => 'insumo', 'ref_recurso' => $id_insumo, 'cantidad' => '10', 'costo_unitario' => '1.80'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('compras', ['ref_sucursal' => $id_sucursal, 'ref_proveedor' => $id_proveedor, 'folio_proveedor' => 'FC-1001', 'importe_total' => 207]);
        $this->assertDatabaseHas('sucursales_productos', ['ref_sucursal' => $id_sucursal, 'ref_producto' => $id_producto, 'existencia_actual' => 8]);
        $this->assertDatabaseHas('sucursales_insumos', ['ref_sucursal' => $id_sucursal, 'ref_insumo' => $id_insumo, 'existencia_actual' => 14]);
        $this->assertDatabaseHas('movimientos_inventario', ['ref_sucursal' => $id_sucursal, 'ref_producto' => $id_producto, 'tipo_movimiento' => 'entrada_compra', 'cantidad' => 6]);
        $this->assertDatabaseHas('movimientos_inventario', ['ref_sucursal' => $id_sucursal, 'ref_insumo' => $id_insumo, 'tipo_movimiento' => 'entrada_compra', 'cantidad' => 10]);
    }

    public function test_reporte_concentra_ventas_pagos_compras_y_alertas_de_inventario(): void
    {
        $usuario = $this->configurarInstalacion();
        $this->actingAs($usuario);
        $id_sucursal = DB::table('sucursales')->value('id_sucursal');
        $ahora = now();

        $this->post('/catalogos/productos', [
            'ref_sucursal' => $id_sucursal, 'nombre' => 'Producto bajo mínimo', 'precio_compra' => '10.00', 'precio_venta' => '20.00',
            'tipo_inventario' => 'unidad', 'existencia_actual' => '1', 'existencia_minima' => '1', 'areas_preparacion' => [],
        ])->assertSessionHasNoErrors();
        DB::table('compras')->insert([
            'ref_sucursal' => $id_sucursal, 'ref_usuario_registro' => $usuario->id_usuario, 'fecha_compra' => $ahora->toDateString(),
            'subtotal' => 68.97, 'importe_iva' => 11.03, 'importe_total' => 80, 'estado' => 'registrada', 'activo' => true,
            'creado_en' => $ahora, 'actualizado_en' => $ahora,
        ]);

        DB::table('pedidos')->insert([
            'ref_sucursal' => $id_sucursal, 'ref_usuario_apertura' => $usuario->id_usuario, 'tipo_servicio' => 'mostrador', 'estado' => 'cerrado',
            'referencia_mostrador' => 'Mostrador 1', 'abierto_en' => $ahora, 'cerrado_en' => $ahora, 'folio_ticket' => 'SX-SM-0001',
            'emitido_ticket_en' => $ahora, 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora,
        ]);
        $id_pedido = DB::table('pedidos')->value('id_pedido');
        DB::table('partes_cuenta_pedidos')->insert([
            'ref_pedido' => $id_pedido, 'nombre' => 'Cuenta general', 'orden' => 1, 'estado' => 'pagada', 'pagada_en' => $ahora,
            'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora,
        ]);
        $id_parte = DB::table('partes_cuenta_pedidos')->value('id_parte_cuenta_pedido');
        DB::table('pagos_partes_cuenta_pedidos')->insert([
            'ref_parte_cuenta_pedido' => $id_parte, 'ref_usuario_registro' => $usuario->id_usuario, 'tipo_pago' => 'efectivo',
            'importe' => 150, 'activo' => true, 'creado_en' => $ahora, 'actualizado_en' => $ahora,
        ]);

        $url_reporte = '/reportes?fecha_inicio='.$ahora->toDateString().'&fecha_fin='.$ahora->toDateString();
        $version_inertia = app(\App\Http\Middleware\HandleInertiaRequests::class)->version(request());

        $this->get($url_reporte, [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Inertia-Version' => $version_inertia,
        ])
            ->assertOk()
            ->assertJsonPath('component', 'Reportes')
            ->assertJsonPath('props.resumen.ventas_cerradas', 1)
            ->assertJsonPath('props.resumen.importe_ventas', 150)
            ->assertJsonPath('props.resumen.importe_compras', 80)
            ->assertJsonPath('props.resumen.alertas_inventario', 1);
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
