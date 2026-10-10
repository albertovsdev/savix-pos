<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventarioOperacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_receta_respeta_sin_ingrediente_extra_y_devuelve_existencias_en_cancelacion_no_preparada(): void
    {
        [$id_sucursal, $id_producto, $id_quesillo, $id_cebolla, $id_extra_quesillo, $id_sin_cebolla] = $this->configurarProductoConReceta();
        $id_pedido = $this->abrirMostradorYEnviar($id_producto, [$id_extra_quesillo, $id_sin_cebolla]);
        $id_detalle = DB::table('detalles_rondas_pedidos')
            ->join('rondas_pedidos', 'rondas_pedidos.id_ronda_pedido', '=', 'detalles_rondas_pedidos.ref_ronda_pedido')
            ->where('rondas_pedidos.ref_pedido', $id_pedido)
            ->value('detalles_rondas_pedidos.id_detalle_ronda_pedido');

        $this->assertDatabaseHas('sucursales_insumos', ['ref_sucursal' => $id_sucursal, 'ref_insumo' => $id_quesillo, 'existencia_actual' => 7]);
        $this->assertDatabaseHas('sucursales_insumos', ['ref_sucursal' => $id_sucursal, 'ref_insumo' => $id_cebolla, 'existencia_actual' => 10]);
        $this->assertDatabaseHas('movimientos_inventario', ['ref_detalle_ronda_pedido' => $id_detalle, 'ref_insumo' => $id_quesillo, 'tipo_movimiento' => 'salida_venta', 'cantidad' => -3]);
        $this->assertDatabaseMissing('movimientos_inventario', ['ref_detalle_ronda_pedido' => $id_detalle, 'ref_insumo' => $id_cebolla, 'tipo_movimiento' => 'salida_venta']);

        $this->post("/operacion/detalles/{$id_detalle}/cancelar", [
            'ref_sucursal' => $id_sucursal,
            'motivo' => 'El cliente cambió de opinión.',
            'nombre_usuario_autorizacion' => 'admin.medel',
            'pin_autorizacion' => '1234',
            'fue_preparado' => false,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sucursales_insumos', ['ref_sucursal' => $id_sucursal, 'ref_insumo' => $id_quesillo, 'existencia_actual' => 10]);
        $this->assertDatabaseHas('movimientos_inventario', ['ref_detalle_ronda_pedido' => $id_detalle, 'ref_insumo' => $id_quesillo, 'tipo_movimiento' => 'entrada_cancelacion', 'cantidad' => 3]);
    }

    public function test_control_unidad_y_mixto_descuentan_producto_y_receta(): void
    {
        $usuario = $this->configurarInstalacion();
        $this->actingAs($usuario);
        $id_sucursal = DB::table('sucursales')->value('id_sucursal');
        $id_unidad = DB::table('cat_unidades_medida')->where('codigo', 'pieza')->value('id_unidad_medida');
        $this->post('/catalogos/insumos', ['ref_sucursal' => $id_sucursal, 'nombre' => 'Hielo', 'ref_unidad_medida' => $id_unidad, 'costo_unitario' => 1, 'existencia_actual' => 10, 'existencia_minima' => 0])->assertSessionHasNoErrors();
        $id_hielo = DB::table('insumos')->where('nombre', 'Hielo')->value('id_insumo');

        $this->post('/catalogos/productos', ['ref_sucursal' => $id_sucursal, 'nombre' => 'Cerveza', 'precio_compra' => 30, 'precio_venta' => 70, 'tipo_inventario' => 'unidad'])->assertSessionHasNoErrors();
        $id_cerveza = DB::table('productos')->where('nombre', 'Cerveza')->value('id_producto');
        DB::table('sucursales_productos')->where('ref_sucursal', $id_sucursal)->where('ref_producto', $id_cerveza)->update(['existencia_actual' => 5]);
        $this->abrirMostradorYEnviar($id_cerveza, [], 2);
        $this->assertDatabaseHas('sucursales_productos', ['ref_sucursal' => $id_sucursal, 'ref_producto' => $id_cerveza, 'existencia_actual' => 3]);

        $this->post('/catalogos/productos', ['ref_sucursal' => $id_sucursal, 'nombre' => 'Michelada mixta', 'precio_compra' => 40, 'precio_venta' => 100, 'tipo_inventario' => 'mixto'])->assertSessionHasNoErrors();
        $id_michelada = DB::table('productos')->where('nombre', 'Michelada mixta')->value('id_producto');
        DB::table('sucursales_productos')->where('ref_sucursal', $id_sucursal)->where('ref_producto', $id_michelada)->update(['existencia_actual' => 4]);
        $this->post('/catalogos/recetas', ['ref_sucursal' => $id_sucursal, 'ref_producto' => $id_michelada, 'ref_insumo' => $id_hielo, 'cantidad' => 2])->assertSessionHasNoErrors();
        $this->abrirMostradorYEnviar($id_michelada);

        $this->assertDatabaseHas('sucursales_productos', ['ref_sucursal' => $id_sucursal, 'ref_producto' => $id_michelada, 'existencia_actual' => 3]);
        $this->assertDatabaseHas('sucursales_insumos', ['ref_sucursal' => $id_sucursal, 'ref_insumo' => $id_hielo, 'existencia_actual' => 8]);
    }

    private function configurarProductoConReceta(): array
    {
        $usuario = $this->configurarInstalacion();
        $this->actingAs($usuario);
        $id_sucursal = DB::table('sucursales')->value('id_sucursal');
        $id_unidad = DB::table('cat_unidades_medida')->where('codigo', 'pieza')->value('id_unidad_medida');
        foreach (['Quesillo', 'Cebolla'] as $nombre) {
            $this->post('/catalogos/insumos', ['ref_sucursal' => $id_sucursal, 'nombre' => $nombre, 'ref_unidad_medida' => $id_unidad, 'costo_unitario' => 1, 'existencia_actual' => 10, 'existencia_minima' => 0])->assertSessionHasNoErrors();
        }
        $id_quesillo = DB::table('insumos')->where('nombre', 'Quesillo')->value('id_insumo');
        $id_cebolla = DB::table('insumos')->where('nombre', 'Cebolla')->value('id_insumo');
        $this->post('/catalogos/productos', ['ref_sucursal' => $id_sucursal, 'nombre' => 'Cemita', 'precio_compra' => 40, 'precio_venta' => 100, 'tipo_inventario' => 'receta'])->assertSessionHasNoErrors();
        $id_producto = DB::table('productos')->where('nombre', 'Cemita')->value('id_producto');
        $this->post('/catalogos/recetas', ['ref_sucursal' => $id_sucursal, 'ref_producto' => $id_producto, 'ref_insumo' => $id_quesillo, 'cantidad' => 2])->assertSessionHasNoErrors();
        $this->post('/catalogos/recetas', ['ref_sucursal' => $id_sucursal, 'ref_producto' => $id_producto, 'ref_insumo' => $id_cebolla, 'cantidad' => 1])->assertSessionHasNoErrors();
        $this->post('/catalogos/grupos-modificadores', ['ref_sucursal' => $id_sucursal, 'ref_producto' => $id_producto, 'nombre' => 'Preparación', 'minimo_selecciones' => 0, 'maximo_selecciones' => 2])->assertSessionHasNoErrors();
        $id_grupo = DB::table('grupos_modificadores_productos')->where('ref_producto', $id_producto)->value('id_grupo_modificador_producto');
        $this->post('/catalogos/opciones-modificadores', ['ref_sucursal' => $id_sucursal, 'ref_grupo_modificador_producto' => $id_grupo, 'nombre' => 'Sin cebolla', 'tipo_modificacion' => 'eliminar_insumo', 'ref_insumo' => $id_cebolla, 'cantidad_insumo' => 0, 'precio_adicional' => 0])->assertSessionHasNoErrors();
        $this->post('/catalogos/opciones-modificadores', ['ref_sucursal' => $id_sucursal, 'ref_grupo_modificador_producto' => $id_grupo, 'nombre' => 'Extra quesillo', 'tipo_modificacion' => 'agregar_insumo', 'ref_insumo' => $id_quesillo, 'cantidad_insumo' => 1, 'precio_adicional' => 10])->assertSessionHasNoErrors();
        $id_opcion = DB::table('opciones_modificadores_productos')->where('nombre', 'Extra quesillo')->value('id_opcion_modificador_producto');
        $id_sin_cebolla = DB::table('opciones_modificadores_productos')->where('nombre', 'Sin cebolla')->value('id_opcion_modificador_producto');

        return [$id_sucursal, $id_producto, $id_quesillo, $id_cebolla, $id_opcion, $id_sin_cebolla];
    }

    private function abrirMostradorYEnviar(int $id_producto, array $opciones = [], int $cantidad = 1): int
    {
        $this->post('/operacion/mostrador')->assertRedirect();
        $id_pedido = DB::table('pedidos')->orderByDesc('id_pedido')->value('id_pedido');
        $this->post("/operacion/pedidos/{$id_pedido}/detalles", ['ref_producto' => $id_producto, 'cantidad' => $cantidad, 'opciones_modificadores' => $opciones])->assertSessionHasNoErrors();
        $this->post("/operacion/pedidos/{$id_pedido}/confirmar-ronda")->assertSessionHasNoErrors();

        return $id_pedido;
    }

    private function configurarInstalacion(): Usuario
    {
        $this->seed(DatabaseSeeder::class);
        $this->post('/configuracion-inicial', ['nombre_negocio' => 'Restaurante Bar MEDEL', 'nombre_comercial' => 'MEDEL', 'clave_folio' => 'SX', 'nombre_sucursal' => 'Matriz', 'clave_sucursal' => 'SM', 'nombre_admin' => 'Administrador MEDEL', 'nombre_usuario' => 'admin.medel', 'correo' => null, 'contrasena' => 'Contrasena-segura-123', 'contrasena_confirmation' => 'Contrasena-segura-123', 'pin' => '1234', 'pin_confirmation' => '1234'])->assertRedirect('/panel');

        return Usuario::query()->where('nombre_usuario', 'admin.medel')->firstOrFail();
    }
}
