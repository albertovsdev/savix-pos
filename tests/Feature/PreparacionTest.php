<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PreparacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_recibe_notas_modificadores_y_avanza_sus_estados(): void
    {
        $this->configurarInstalacion();
        [$id_sucursal, $id_area, $id_producto] = $this->crearProductoParaBarra();
        $this->post('/catalogos/grupos-modificadores', [
            'ref_sucursal' => $id_sucursal, 'ref_producto' => $id_producto, 'nombre' => 'Preparación',
            'minimo_selecciones' => 0, 'maximo_selecciones' => 1,
        ])->assertSessionHasNoErrors();
        $id_grupo = DB::table('grupos_modificadores_productos')->where('nombre', 'Preparación')->value('id_grupo_modificador_producto');
        $this->post('/catalogos/opciones-modificadores', [
            'ref_sucursal' => $id_sucursal, 'ref_grupo_modificador_producto' => $id_grupo, 'nombre' => 'Sin chile',
            'tipo_modificacion' => 'nota', 'precio_adicional' => '0.00',
        ])->assertSessionHasNoErrors();
        $id_opcion = DB::table('opciones_modificadores_productos')->where('nombre', 'Sin chile')->value('id_opcion_modificador_producto');

        $id_detalle_area = $this->enviarProductoAPreparacion($id_sucursal, $id_producto, $id_opcion);

        $this->get('/preparacion')->assertInertia(fn (Assert $pagina) => $pagina
            ->component('Preparacion', false)
            ->where('area_seleccionada.id_area_preparacion', $id_area)
            ->has('detalles', 1)
            ->where('detalles.0.nombre_producto', 'Michelada')
            ->where('detalles.0.nota_preparacion', 'Sin sal en el vaso')
            ->where('detalles.0.modificadores.0', 'Sin chile')
        );

        $this->post("/preparacion/detalles/{$id_detalle_area}/estado", ['estado_preparacion' => 'en_preparacion'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('detalles_rondas_pedidos_areas_preparacion', ['id_detalle_ronda_pedido_area_preparacion' => $id_detalle_area, 'estado_preparacion' => 'en_preparacion']);
        $this->post("/preparacion/detalles/{$id_detalle_area}/estado", ['estado_preparacion' => 'listo'])->assertSessionHasNoErrors();
        $this->post("/preparacion/detalles/{$id_detalle_area}/estado", ['estado_preparacion' => 'entregado'])->assertSessionHasNoErrors();
        $this->get('/preparacion')->assertInertia(fn (Assert $pagina) => $pagina->has('detalles', 0));
    }

    public function test_usuario_de_barra_solo_consulta_las_areas_asignadas(): void
    {
        $this->configurarInstalacion();
        [$id_sucursal, $id_barra] = $this->crearProductoParaBarra();
        $this->post('/catalogos/areas-preparacion', [
            'ref_sucursal' => $id_sucursal, 'nombre' => 'Cocina', 'codigo' => 'COCINA', 'orden' => 20,
        ])->assertSessionHasNoErrors();
        $id_rol_barra = DB::table('cat_roles')->where('codigo', 'barra')->value('id_rol');
        $this->post('/administracion/usuarios', [
            'nombre' => 'Barra MEDEL', 'nombre_usuario' => 'barra.medel', 'correo' => null,
            'contrasena' => 'Contrasena-segura-123', 'contrasena_confirmation' => 'Contrasena-segura-123',
            'pin' => '4321', 'pin_confirmation' => '4321', 'ref_sucursal' => $id_sucursal, 'ref_rol' => $id_rol_barra,
        ])->assertSessionHasNoErrors();
        $usuario_barra = Usuario::query()->where('nombre_usuario', 'barra.medel')->firstOrFail();

        $this->put("/administracion/usuarios/{$usuario_barra->id_usuario}/areas-preparacion", [
            'ref_sucursal' => $id_sucursal, 'areas_preparacion' => [$id_barra],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuarios_areas_preparacion', ['ref_usuario' => $usuario_barra->id_usuario, 'ref_area_preparacion' => $id_barra, 'activo' => true]);

        $this->actingAs($usuario_barra)->get('/preparacion')->assertInertia(fn (Assert $pagina) => $pagina
            ->component('Preparacion', false)
            ->has('areas', 1)
            ->where('areas.0.id_area_preparacion', $id_barra)
        );
        $id_cocina = DB::table('areas_preparacion')->where('codigo', 'COCINA')->value('id_area_preparacion');
        $this->actingAs($usuario_barra)->get("/preparacion?ref_area_preparacion={$id_cocina}")->assertNotFound();
    }

    private function crearProductoParaBarra(): array
    {
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

        return [$id_sucursal, $id_area, DB::table('productos')->where('nombre', 'Michelada')->value('id_producto')];
    }

    private function enviarProductoAPreparacion(int $id_sucursal, int $id_producto, ?int $id_opcion = null): int
    {
        $this->post('/operacion/mesas', ['nombre' => 'Mesa 1', 'orden' => 1])->assertSessionHasNoErrors();
        $id_mesa = DB::table('mesas')->where('nombre', 'Mesa 1')->value('id_mesa');
        $this->post("/operacion/mesas/{$id_mesa}/abrir", ['ref_sucursal' => $id_sucursal])->assertRedirect();
        $id_pedido = DB::table('pedidos')->where('ref_mesa', $id_mesa)->value('id_pedido');
        $this->post("/operacion/pedidos/{$id_pedido}/detalles", [
            'ref_sucursal' => $id_sucursal, 'ref_producto' => $id_producto, 'cantidad' => '1',
            'nota_preparacion' => 'Sin sal en el vaso', 'opciones_modificadores' => $id_opcion ? [$id_opcion] : [],
        ])->assertSessionHasNoErrors();
        $this->post("/operacion/pedidos/{$id_pedido}/confirmar-ronda", ['ref_sucursal' => $id_sucursal])->assertSessionHasNoErrors();

        return DB::table('detalles_rondas_pedidos_areas_preparacion')->value('id_detalle_ronda_pedido_area_preparacion');
    }

    private function configurarInstalacion(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->post('/configuracion-inicial', [
            'nombre_negocio' => 'Restaurante Bar MEDEL', 'nombre_comercial' => 'MEDEL', 'clave_folio' => 'SX',
            'nombre_sucursal' => 'Matriz', 'clave_sucursal' => 'SM', 'nombre_admin' => 'Administrador MEDEL',
            'nombre_usuario' => 'admin.medel', 'correo' => null, 'contrasena' => 'Contrasena-segura-123',
            'contrasena_confirmation' => 'Contrasena-segura-123', 'pin' => '1234', 'pin_confirmation' => '1234',
        ])->assertRedirect('/panel');
    }
}
