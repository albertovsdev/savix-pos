<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use App\Models\Sucursal;
use App\Services\ServicioInventario;
use App\Services\ServicioPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ComprasControlador extends Controller
{
    public function __construct(
        private readonly ServicioPermisos $servicio_permisos,
        private readonly ServicioInventario $servicio_inventario,
    ) {
    }

    public function mostrar(Request $solicitud): Response
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'compras.ver', $sucursal->id_sucursal);
        $negocio = Negocio::query()->firstOrFail();

        return Inertia::render('Compras', [
            'sucursal_seleccionada' => $sucursal->only(['id_sucursal', 'nombre', 'clave']),
            'sucursales' => $this->sucursalesUsuario($solicitud),
            'proveedores' => DB::table('proveedores')->where('ref_negocio', $negocio->id_negocio)->where('activo', true)->orderBy('nombre')->get(),
            'recursos' => $this->recursosComprables($sucursal->id_sucursal),
            'compras' => $this->ultimasCompras($sucursal->id_sucursal),
            'puede_registrar' => $this->servicio_permisos->tiene($solicitud->user(), 'compras.registrar', $sucursal->id_sucursal),
        ]);
    }

    public function crearProveedor(Request $solicitud): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'compras.registrar', $sucursal->id_sucursal);
        $negocio = Negocio::query()->firstOrFail();
        $datos = $solicitud->validate([
            'codigo' => ['nullable', 'alpha_dash', 'max:80', Rule::unique('proveedores')->where('ref_negocio', $negocio->id_negocio)],
            'nombre' => ['required', 'string', 'max:160'],
            'contacto' => ['nullable', 'string', 'max:160'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:191'],
            'direccion' => ['nullable', 'string', 'max:2000'],
        ]);

        $id_proveedor = DB::table('proveedores')->insertGetId([
            ...$datos,
            'ref_negocio' => $negocio->id_negocio,
            'codigo' => $datos['codigo'] ?? null,
            'activo' => true,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
        $this->registrar($solicitud, 'crear_proveedor', 'proveedores', $id_proveedor, $datos, $sucursal->id_sucursal);

        return back()->with('exito', 'El proveedor fue creado.');
    }

    public function registrarCompra(Request $solicitud): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'compras.registrar', $sucursal->id_sucursal);
        $negocio = Negocio::query()->firstOrFail();
        $datos = $solicitud->validate([
            'ref_proveedor' => ['nullable', 'integer'],
            'fecha_compra' => ['required', 'date'],
            'folio_proveedor' => ['nullable', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:5000'],
            'detalles' => ['required', 'array', 'min:1', 'max:100'],
            'detalles.*.tipo_recurso' => ['required', Rule::in(['producto', 'insumo'])],
            'detalles.*.ref_recurso' => ['required', 'integer'],
            'detalles.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:99999999.9999'],
            'detalles.*.costo_unitario' => ['required', 'numeric', 'min:0', 'max:99999999.9999'],
        ]);

        if ($datos['ref_proveedor'] ?? null) {
            abort_unless(DB::table('proveedores')->where('id_proveedor', $datos['ref_proveedor'])->where('ref_negocio', $negocio->id_negocio)->where('activo', true)->exists(), 422);
        }

        $resultado = DB::transaction(function () use ($datos, $sucursal, $negocio, $solicitud): array {
            $detalles = collect($datos['detalles'])->map(function (array $detalle) use ($sucursal): array {
                $recurso = $this->recursoComprable($sucursal->id_sucursal, $detalle['tipo_recurso'], (int) $detalle['ref_recurso']);
                abort_unless($recurso, 422, 'Uno de los recursos no está disponible para la sucursal.');

                $cantidad = (float) $detalle['cantidad'];
                $costo_unitario = (float) $detalle['costo_unitario'];
                return [
                    'tipo_recurso' => $detalle['tipo_recurso'], 'ref_recurso' => (int) $detalle['ref_recurso'], 'nombre_recurso' => $recurso->nombre,
                    'cantidad' => $cantidad, 'costo_unitario' => $costo_unitario, 'importe_total' => round($cantidad * $costo_unitario, 2),
                ];
            });
            $importe_total = round($detalles->sum('importe_total'), 2);
            $factor_iva = 1 + ((float) $negocio->porcentaje_iva / 100);
            $subtotal = $negocio->precios_incluyen_iva ? round($importe_total / $factor_iva, 2) : $importe_total;
            $importe_iva = round($importe_total - $subtotal, 2);

            $id_compra = DB::table('compras')->insertGetId([
                'ref_sucursal' => $sucursal->id_sucursal,
                'ref_proveedor' => $datos['ref_proveedor'] ?? null,
                'ref_usuario_registro' => $solicitud->user()->id_usuario,
                'folio_proveedor' => $datos['folio_proveedor'] ?? null,
                'fecha_compra' => $datos['fecha_compra'],
                'subtotal' => $subtotal,
                'importe_iva' => $importe_iva,
                'importe_total' => $importe_total,
                'notas' => $datos['notas'] ?? null,
                'estado' => 'registrada',
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            foreach ($detalles as $detalle) {
                DB::table('detalles_compras')->insert([
                    'ref_compra' => $id_compra,
                    'ref_producto' => $detalle['tipo_recurso'] === 'producto' ? $detalle['ref_recurso'] : null,
                    'ref_insumo' => $detalle['tipo_recurso'] === 'insumo' ? $detalle['ref_recurso'] : null,
                    'nombre_recurso' => $detalle['nombre_recurso'],
                    'cantidad' => $detalle['cantidad'],
                    'costo_unitario' => $detalle['costo_unitario'],
                    'importe_total' => $detalle['importe_total'],
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
                $this->servicio_inventario->registrarEntradaCompra(
                    $sucursal->id_sucursal,
                    $detalle['tipo_recurso'],
                    $detalle['ref_recurso'],
                    $detalle['cantidad'],
                    "Compra #{$id_compra}: {$detalle['nombre_recurso']}",
                );
            }

            return ['id_compra' => $id_compra, 'importe_total' => $importe_total, 'cantidad_detalles' => $detalles->count()];
        });

        $this->registrar($solicitud, 'registrar_compra', 'compras', $resultado['id_compra'], [
            'importe_total' => $resultado['importe_total'], 'cantidad_detalles' => $resultado['cantidad_detalles'],
        ], $sucursal->id_sucursal);

        return back()->with('exito', 'La compra fue registrada y las existencias fueron actualizadas.');
    }

    private function recursosComprables(int $id_sucursal): Collection
    {
        $productos = DB::table('sucursales_productos')->join('productos', 'productos.id_producto', '=', 'sucursales_productos.ref_producto')
            ->where('sucursales_productos.ref_sucursal', $id_sucursal)->where('sucursales_productos.activo', true)->where('productos.activo', true)
            ->whereIn('productos.tipo_inventario', ['unidad', 'mixto'])->orderBy('productos.nombre')
            ->get(['productos.id_producto as id_recurso', 'productos.nombre', 'productos.precio_compra as costo_sugerido', DB::raw("'producto' as tipo_recurso")]);
        $insumos = DB::table('sucursales_insumos')->join('insumos', 'insumos.id_insumo', '=', 'sucursales_insumos.ref_insumo')
            ->where('sucursales_insumos.ref_sucursal', $id_sucursal)->where('sucursales_insumos.activo', true)->where('insumos.activo', true)
            ->orderBy('insumos.nombre')->get(['insumos.id_insumo as id_recurso', 'insumos.nombre', 'insumos.costo_unitario as costo_sugerido', DB::raw("'insumo' as tipo_recurso")]);

        return $productos->merge($insumos)->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    private function recursoComprable(int $id_sucursal, string $tipo_recurso, int $id_recurso): ?object
    {
        return $tipo_recurso === 'producto'
            ? DB::table('sucursales_productos')->join('productos', 'productos.id_producto', '=', 'sucursales_productos.ref_producto')
                ->where('sucursales_productos.ref_sucursal', $id_sucursal)->where('sucursales_productos.ref_producto', $id_recurso)
                ->where('sucursales_productos.activo', true)->where('productos.activo', true)->whereIn('productos.tipo_inventario', ['unidad', 'mixto'])->first(['productos.nombre'])
            : DB::table('sucursales_insumos')->join('insumos', 'insumos.id_insumo', '=', 'sucursales_insumos.ref_insumo')
                ->where('sucursales_insumos.ref_sucursal', $id_sucursal)->where('sucursales_insumos.ref_insumo', $id_recurso)
                ->where('sucursales_insumos.activo', true)->where('insumos.activo', true)->first(['insumos.nombre']);
    }

    private function ultimasCompras(int $id_sucursal): Collection
    {
        return DB::table('compras')->leftJoin('proveedores', 'proveedores.id_proveedor', '=', 'compras.ref_proveedor')
            ->where('compras.ref_sucursal', $id_sucursal)->where('compras.activo', true)->orderByDesc('compras.fecha_compra')->orderByDesc('compras.id_compra')->limit(30)
            ->get(['compras.id_compra', 'compras.fecha_compra', 'compras.folio_proveedor', 'compras.importe_total', 'compras.estado', 'proveedores.nombre as proveedor']);
    }

    private function sucursalesUsuario(Request $solicitud): Collection
    {
        return Sucursal::query()->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)->where('usuarios_sucursales.activo', true)->where('sucursales.activo', true)
            ->orderByDesc('usuarios_sucursales.es_principal')->orderBy('sucursales.nombre')->get(['sucursales.id_sucursal', 'sucursales.nombre', 'sucursales.clave']);
    }

    private function sucursalSeleccionada(Request $solicitud): Sucursal
    {
        return Sucursal::query()->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)->where('usuarios_sucursales.activo', true)->where('sucursales.activo', true)
            ->when($solicitud->integer('ref_sucursal'), fn ($consulta, int $id_sucursal) => $consulta->where('sucursales.id_sucursal', $id_sucursal))
            ->orderByDesc('usuarios_sucursales.es_principal')->select('sucursales.*')->firstOrFail();
    }

    private function autorizar(Request $solicitud, string $permiso, int $id_sucursal): void
    {
        abort_unless($this->servicio_permisos->tiene($solicitud->user(), $permiso, $id_sucursal), 403);
    }

    private function registrar(Request $solicitud, string $accion, string $entidad, int $id_entidad, array $datos_nuevos, int $id_sucursal): void
    {
        DB::table('bitacora_auditoria')->insert([
            'ref_usuario' => $solicitud->user()->id_usuario, 'ref_sucursal' => $id_sucursal, 'modulo' => 'compras',
            'accion' => $accion, 'entidad' => $entidad, 'ref_entidad' => $id_entidad, 'datos_nuevos' => json_encode($datos_nuevos),
            'direccion_ip' => $solicitud->ip(), 'agente_usuario' => $solicitud->userAgent(), 'creado_en' => now(), 'actualizado_en' => now(),
        ]);
    }
}
