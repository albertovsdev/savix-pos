<?php

namespace App\Http\Controllers;

use App\Models\AreaPreparacion;
use App\Models\CategoriaProducto;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Services\ServicioPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CatalogosControlador extends Controller
{
    public function __construct(private readonly ServicioPermisos $servicio_permisos)
    {
    }

    public function mostrar(Request $solicitud): Response
    {
        $negocio = Negocio::query()->firstOrFail();
        $this->autorizar($solicitud, 'catalogos.ver');
        $sucursal = $this->sucursalSeleccionada($solicitud, $negocio);

        $categorias = DB::table('categorias_productos')
            ->leftJoin('sucursales_categorias_productos', function ($union) use ($sucursal): void {
                $union->on('sucursales_categorias_productos.ref_categoria_producto', '=', 'categorias_productos.id_categoria_producto')
                    ->where('sucursales_categorias_productos.ref_sucursal', $sucursal->id_sucursal);
            })
            ->where('categorias_productos.ref_negocio', $negocio->id_negocio)
            ->where('categorias_productos.activo', true)
            ->orderBy('categorias_productos.orden')
            ->orderBy('categorias_productos.nombre')
            ->select([
                'categorias_productos.id_categoria_producto', 'categorias_productos.nombre', 'categorias_productos.descripcion', 'categorias_productos.orden',
                DB::raw('COALESCE(sucursales_categorias_productos.habilitada, 1) as habilitada'),
            ])
            ->get();

        $productos = DB::table('productos')
            ->leftJoin('categorias_productos', 'categorias_productos.id_categoria_producto', '=', 'productos.ref_categoria_producto')
            ->leftJoin('sucursales_productos', function ($union) use ($sucursal): void {
                $union->on('sucursales_productos.ref_producto', '=', 'productos.id_producto')
                    ->where('sucursales_productos.ref_sucursal', $sucursal->id_sucursal);
            })
            ->where('productos.ref_negocio', $negocio->id_negocio)
            ->where('productos.activo', true)
            ->orderBy('categorias_productos.orden')
            ->orderBy('productos.nombre')
            ->select([
                'productos.id_producto', 'productos.codigo', 'productos.nombre', 'productos.precio_compra', 'productos.precio_venta', 'productos.tipo_inventario',
                'categorias_productos.nombre as nombre_categoria', DB::raw('COALESCE(sucursales_productos.habilitado, 1) as habilitado'),
            ])
            ->get();

        $productos = $productos->map(function (object $producto) use ($sucursal): object {
            $producto->categoria = $producto->nombre_categoria;
            $producto->tipo_inventario_nombre = match ($producto->tipo_inventario) {
                'unidad' => 'Descuenta unidades',
                'receta' => 'Descuenta receta',
                'mixto' => 'Control mixto',
                default => 'Sin control de inventario',
            };
            $producto->areas_preparacion = DB::table('sucursales_productos_areas_preparacion')
                ->join('sucursales_productos', 'sucursales_productos.id_sucursal_producto', '=', 'sucursales_productos_areas_preparacion.ref_sucursal_producto')
                ->join('areas_preparacion', 'areas_preparacion.id_area_preparacion', '=', 'sucursales_productos_areas_preparacion.ref_area_preparacion')
                ->where('sucursales_productos.ref_sucursal', $sucursal->id_sucursal)
                ->where('sucursales_productos.ref_producto', $producto->id_producto)
                ->where('sucursales_productos_areas_preparacion.activo', true)
                ->orderBy('areas_preparacion.orden')
                ->pluck('areas_preparacion.nombre')
                ->all();

            return $producto;
        });

        return Inertia::render('Catalogos', [
            'negocio' => $negocio->only(['id_negocio', 'nombre_comercial']),
            'sucursal_seleccionada' => $sucursal->only(['id_sucursal', 'clave', 'nombre']),
            'sucursales' => Sucursal::query()->where('ref_negocio', $negocio->id_negocio)->where('activo', true)->orderBy('nombre')->get(['id_sucursal', 'clave', 'nombre']),
            'categorias' => $categorias,
            'productos' => $productos,
            'areas_preparacion' => AreaPreparacion::query()->where('ref_sucursal', $sucursal->id_sucursal)->where('activo', true)->orderBy('orden')->orderBy('nombre')->get(['id_area_preparacion', 'nombre', 'codigo']),
            'tipos_inventario' => [
                ['valor' => 'sin_control', 'nombre' => 'Sin control de inventario'],
                ['valor' => 'unidad', 'nombre' => 'Descuenta unidades'],
                ['valor' => 'receta', 'nombre' => 'Descuenta receta'],
                ['valor' => 'mixto', 'nombre' => 'Control mixto'],
            ],
            'puede_gestionar' => $this->servicio_permisos->tiene($solicitud->user(), 'catalogos.gestionar'),
        ]);
    }

    public function crearCategoria(Request $solicitud): RedirectResponse
    {
        $negocio = Negocio::query()->firstOrFail();
        $this->autorizar($solicitud, 'catalogos.gestionar');

        $datos = $solicitud->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('categorias_productos')->where('ref_negocio', $negocio->id_negocio)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $categoria = DB::transaction(function () use ($datos, $negocio): CategoriaProducto {
            $categoria = CategoriaProducto::query()->create([
                ...$datos,
                'ref_negocio' => $negocio->id_negocio,
                'orden' => $datos['orden'] ?? 0,
                'activo' => true,
            ]);

            foreach (Sucursal::query()->where('ref_negocio', $negocio->id_negocio)->pluck('id_sucursal') as $id_sucursal) {
                DB::table('sucursales_categorias_productos')->insert([
                    'ref_sucursal' => $id_sucursal,
                    'ref_categoria_producto' => $categoria->id_categoria_producto,
                    'habilitada' => true,
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }

            return $categoria;
        });

        $this->registrar($solicitud, 'crear_categoria', 'categorias_productos', $categoria->id_categoria_producto, $datos);

        return back()->with('exito', 'La categoría fue creada y está disponible en las sucursales.');
    }

    public function crearAreaPreparacion(Request $solicitud): RedirectResponse
    {
        $negocio = Negocio::query()->firstOrFail();
        $sucursal = $this->sucursalSeleccionada($solicitud, $negocio);
        $this->autorizar($solicitud, 'catalogos.gestionar', $sucursal->id_sucursal);

        $datos = $solicitud->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'codigo' => ['nullable', 'alpha_dash', 'max:40', Rule::unique('areas_preparacion')->where('ref_sucursal', $sucursal->id_sucursal)],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $codigo = $datos['codigo'] ?: Str::upper(Str::slug($datos['nombre'], '-'));
        $area = AreaPreparacion::query()->create([
            ...$datos,
            'ref_sucursal' => $sucursal->id_sucursal,
            'codigo' => $codigo,
            'orden' => $datos['orden'] ?? 0,
            'activo' => true,
        ]);

        $this->registrar($solicitud, 'crear_area_preparacion', 'areas_preparacion', $area->id_area_preparacion, $datos, $sucursal->id_sucursal);

        return back()->with('exito', 'El área de preparación fue creada.');
    }

    public function crearProducto(Request $solicitud): RedirectResponse
    {
        $negocio = Negocio::query()->firstOrFail();
        $sucursal = $this->sucursalSeleccionada($solicitud, $negocio);
        $this->autorizar($solicitud, 'catalogos.gestionar', $sucursal->id_sucursal);

        $datos = $solicitud->validate([
            'ref_categoria_producto' => ['nullable', Rule::exists('categorias_productos', 'id_categoria_producto')->where('ref_negocio', $negocio->id_negocio)],
            'codigo' => ['nullable', 'alpha_dash', 'max:80', Rule::unique('productos')->where('ref_negocio', $negocio->id_negocio)],
            'nombre' => ['required', 'string', 'max:160'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'precio_compra' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'tipo_inventario' => ['required', Rule::in(['sin_control', 'unidad', 'receta', 'mixto'])],
            'areas_preparacion' => ['nullable', 'array'],
            'areas_preparacion.*' => ['integer', 'exists:areas_preparacion,id_area_preparacion'],
        ]);

        $ids_areas = $datos['areas_preparacion'] ?? [];
        abort_unless(
            count($ids_areas) === DB::table('areas_preparacion')->where('ref_sucursal', $sucursal->id_sucursal)->whereIn('id_area_preparacion', $ids_areas)->count(),
            422,
        );

        $producto = DB::transaction(function () use ($datos, $ids_areas, $negocio, $sucursal): Producto {
            $producto = Producto::query()->create([
                ...collect($datos)->except('areas_preparacion')->all(),
                'ref_negocio' => $negocio->id_negocio,
                'codigo' => $datos['codigo'] ?? null,
                'ref_categoria_producto' => $datos['ref_categoria_producto'] ?: null,
                'activo' => true,
            ]);

            foreach (Sucursal::query()->where('ref_negocio', $negocio->id_negocio)->pluck('id_sucursal') as $id_sucursal) {
                DB::table('sucursales_productos')->insert([
                    'ref_sucursal' => $id_sucursal,
                    'ref_producto' => $producto->id_producto,
                    'habilitado' => true,
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }

            $id_sucursal_producto = DB::table('sucursales_productos')
                ->where('ref_sucursal', $sucursal->id_sucursal)
                ->where('ref_producto', $producto->id_producto)
                ->value('id_sucursal_producto');

            foreach ($ids_areas as $id_area) {
                DB::table('sucursales_productos_areas_preparacion')->insert([
                    'ref_sucursal_producto' => $id_sucursal_producto,
                    'ref_area_preparacion' => $id_area,
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }

            return $producto;
        });

        $this->registrar($solicitud, 'crear_producto', 'productos', $producto->id_producto, collect($datos)->except('areas_preparacion')->all(), $sucursal->id_sucursal);

        return back()->with('exito', 'El producto fue creado y está disponible en las sucursales.');
    }

    public function actualizarDisponibilidadCategoria(Request $solicitud, CategoriaProducto $categoria): RedirectResponse
    {
        $negocio = Negocio::query()->firstOrFail();
        abort_unless($categoria->ref_negocio === $negocio->id_negocio, 404);
        $sucursal = $this->sucursalSeleccionada($solicitud, $negocio);
        $this->autorizar($solicitud, 'catalogos.gestionar', $sucursal->id_sucursal);
        $datos = $solicitud->validate(['habilitada' => ['required', 'boolean']]);

        DB::table('sucursales_categorias_productos')->updateOrInsert(
            ['ref_sucursal' => $sucursal->id_sucursal, 'ref_categoria_producto' => $categoria->id_categoria_producto],
            ['habilitada' => $datos['habilitada'], 'activo' => true, 'actualizado_en' => now(), 'creado_en' => now()],
        );

        return back()->with('exito', 'La disponibilidad de la categoría fue actualizada.');
    }

    public function actualizarDisponibilidadProducto(Request $solicitud, Producto $producto): RedirectResponse
    {
        $negocio = Negocio::query()->firstOrFail();
        abort_unless($producto->ref_negocio === $negocio->id_negocio, 404);
        $sucursal = $this->sucursalSeleccionada($solicitud, $negocio);
        $this->autorizar($solicitud, 'catalogos.gestionar', $sucursal->id_sucursal);
        $datos = $solicitud->validate(['habilitado' => ['required', 'boolean']]);

        DB::table('sucursales_productos')->updateOrInsert(
            ['ref_sucursal' => $sucursal->id_sucursal, 'ref_producto' => $producto->id_producto],
            ['habilitado' => $datos['habilitado'], 'activo' => true, 'actualizado_en' => now(), 'creado_en' => now()],
        );

        return back()->with('exito', 'La disponibilidad del producto fue actualizada.');
    }

    private function sucursalSeleccionada(Request $solicitud, Negocio $negocio): Sucursal
    {
        $id_sucursal = $solicitud->integer('ref_sucursal');

        return Sucursal::query()
            ->where('ref_negocio', $negocio->id_negocio)
            ->where('activo', true)
            ->when($id_sucursal, fn ($consulta) => $consulta->where('id_sucursal', $id_sucursal))
            ->orderBy('nombre')
            ->firstOrFail();
    }

    private function autorizar(Request $solicitud, string $permiso, ?int $id_sucursal = null): void
    {
        abort_unless($this->servicio_permisos->tiene($solicitud->user(), $permiso, $id_sucursal), 403);
    }

    private function registrar(Request $solicitud, string $accion, string $entidad, int $id_entidad, array $datos_nuevos, ?int $id_sucursal = null): void
    {
        DB::table('bitacora_auditoria')->insert([
            'ref_usuario' => $solicitud->user()->id_usuario,
            'ref_sucursal' => $id_sucursal,
            'modulo' => 'catalogos',
            'accion' => $accion,
            'entidad' => $entidad,
            'ref_entidad' => $id_entidad,
            'datos_nuevos' => json_encode($datos_nuevos),
            'direccion_ip' => $solicitud->ip(),
            'agente_usuario' => $solicitud->userAgent(),
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
    }
}
