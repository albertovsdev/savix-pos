<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogosTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_gestiona_catalogo_y_disponibilidad_por_sucursal(): void
    {
        $administrador = $this->configurarInstalacion();
        $id_negocio = DB::table('negocios')->value('id_negocio');
        $id_matriz = DB::table('sucursales')->value('id_sucursal');

        DB::table('sucursales')->insert([
            'ref_negocio' => $id_negocio, 'clave' => 'SN', 'nombre' => 'Sucursal Norte', 'activo' => true,
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $id_norte = DB::table('sucursales')->where('clave', 'SN')->value('id_sucursal');

        $this->get('/catalogos')->assertInertia(fn (Assert $pagina) => $pagina
            ->component('Catalogos', false)
            ->has('negocio')
        );
        $this->post('/catalogos/categorias', ['nombre' => 'Bebidas', 'descripcion' => 'Bebidas frias y preparadas', 'orden' => 10])->assertSessionHasNoErrors();

        $id_categoria = DB::table('categorias_productos')->where('nombre', 'Bebidas')->value('id_categoria_producto');
        $this->assertDatabaseCount('sucursales_categorias_productos', 2);

        $this->post('/catalogos/areas-preparacion', [
            'ref_sucursal' => $id_matriz, 'nombre' => 'Barra', 'codigo' => 'BARRA', 'orden' => 10,
        ])->assertSessionHasNoErrors();
        $id_area = DB::table('areas_preparacion')->where('codigo', 'BARRA')->value('id_area_preparacion');

        $this->post('/catalogos/productos', [
            'ref_sucursal' => $id_matriz, 'ref_categoria_producto' => $id_categoria, 'codigo' => 'CAG-MEDIO',
            'nombre' => 'Caguama media', 'descripcion' => null, 'precio_compra' => '30.00', 'precio_venta' => '70.00',
            'tipo_inventario' => 'unidad', 'areas_preparacion' => [$id_area],
        ])->assertSessionHasNoErrors();

        $id_producto = DB::table('productos')->where('codigo', 'CAG-MEDIO')->value('id_producto');
        $this->assertDatabaseCount('sucursales_productos', 2);
        $this->assertDatabaseHas('sucursales_productos_areas_preparacion', ['ref_area_preparacion' => $id_area]);

        $this->put("/catalogos/productos/{$id_producto}/disponibilidad", [
            'ref_sucursal' => $id_norte, 'habilitado' => false,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sucursales_productos', ['ref_sucursal' => $id_norte, 'ref_producto' => $id_producto, 'habilitado' => false]);
        $this->assertDatabaseHas('sucursales_productos', ['ref_sucursal' => $id_matriz, 'ref_producto' => $id_producto, 'habilitado' => true]);
        $this->assertDatabaseHas('bitacora_auditoria', ['ref_usuario' => $administrador->id_usuario, 'accion' => 'crear_producto']);
    }

    public function test_nueva_sucursal_recibe_el_catalogo_existente(): void
    {
        $this->configurarInstalacion();

        $this->post('/catalogos/categorias', ['nombre' => 'Tostadas', 'orden' => 10])->assertSessionHasNoErrors();
        $id_categoria = DB::table('categorias_productos')->where('nombre', 'Tostadas')->value('id_categoria_producto');
        $id_matriz = DB::table('sucursales')->value('id_sucursal');
        $this->post('/catalogos/productos', [
            'ref_sucursal' => $id_matriz, 'ref_categoria_producto' => $id_categoria, 'nombre' => 'Tostada camaron',
            'precio_compra' => '40.00', 'precio_venta' => '90.00', 'tipo_inventario' => 'receta', 'areas_preparacion' => [],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productos', ['nombre' => 'Tostada camaron']);

        $this->post('/administracion/sucursales', [
            'clave' => 'SO', 'nombre' => 'Sucursal Oriente', 'telefono' => null, 'correo' => null, 'direccion' => null,
            'modulos' => DB::table('cat_modulos')->pluck('id_modulo')->all(),
        ])->assertSessionHasNoErrors();

        $id_oriente = DB::table('sucursales')->where('clave', 'SO')->value('id_sucursal');
        $this->assertDatabaseHas('sucursales_categorias_productos', ['ref_sucursal' => $id_oriente, 'ref_categoria_producto' => $id_categoria, 'habilitada' => true]);
        $this->assertDatabaseHas('sucursales_productos', ['ref_sucursal' => $id_oriente, 'habilitado' => true]);
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
