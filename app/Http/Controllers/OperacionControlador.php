<?php

namespace App\Http\Controllers;

use App\Models\DetalleRondaPedido;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\RondaPedido;
use App\Models\Sucursal;
use App\Services\ServicioPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OperacionControlador extends Controller
{
    public function __construct(private readonly ServicioPermisos $servicio_permisos)
    {
    }

    public function mostrar(Request $solicitud): Response
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'ventas.crear', $sucursal->id_sucursal);

        $mesas = Mesa::query()
            ->leftJoin('pedidos', function ($union) use ($sucursal): void {
                $union->on('pedidos.ref_mesa', '=', 'mesas.id_mesa')
                    ->where('pedidos.ref_sucursal', $sucursal->id_sucursal)
                    ->where('pedidos.estado', 'abierto')
                    ->where('pedidos.activo', true);
            })
            ->where('mesas.ref_sucursal', $sucursal->id_sucursal)
            ->where('mesas.activo', true)
            ->orderBy('mesas.orden')
            ->orderBy('mesas.nombre')
            ->select(['mesas.id_mesa', 'mesas.nombre', 'mesas.estado', 'pedidos.id_pedido as ref_pedido'])
            ->get();

        $productos = $this->productosDisponibles($sucursal->id_sucursal);
        $grupos = DB::table('grupos_modificadores_productos')
            ->where('activo', true)
            ->get(['id_grupo_modificador_producto', 'ref_producto', 'nombre', 'minimo_selecciones', 'maximo_selecciones']);
        $opciones = DB::table('opciones_modificadores_productos')
            ->where('activo', true)
            ->get(['id_opcion_modificador_producto', 'ref_grupo_modificador_producto', 'ref_insumo', 'nombre', 'tipo_modificacion', 'cantidad_insumo', 'precio_adicional']);

        $productos = $productos->map(function (object $producto) use ($grupos, $opciones): object {
            $producto->grupos_modificadores = $grupos
                ->where('ref_producto', $producto->id_producto)
                ->map(function (object $grupo) use ($opciones): object {
                    $grupo->opciones = $opciones
                        ->where('ref_grupo_modificador_producto', $grupo->id_grupo_modificador_producto)
                        ->values();

                    return $grupo;
                })
                ->values();

            return $producto;
        });

        $pedido = $this->pedidoSeleccionado($solicitud, $sucursal->id_sucursal);

        return Inertia::render('Operacion', [
            'sucursal_seleccionada' => $sucursal->only(['id_sucursal', 'nombre', 'clave']),
            'sucursales' => $this->sucursalesUsuario($solicitud),
            'mesas' => $mesas,
            'productos' => $productos,
            'pedido' => $pedido ? $this->presentarPedido($pedido) : null,
            'puede_configurar_mesas' => $this->servicio_permisos->tiene($solicitud->user(), 'catalogos.gestionar', $sucursal->id_sucursal),
        ]);
    }

    public function crearMesa(Request $solicitud): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'catalogos.gestionar', $sucursal->id_sucursal);

        $datos = $solicitud->validate([
            'nombre' => ['required', 'string', 'max:80', Rule::unique('mesas')->where('ref_sucursal', $sucursal->id_sucursal)],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $mesa = Mesa::query()->create([
            ...$datos,
            'ref_sucursal' => $sucursal->id_sucursal,
            'orden' => $datos['orden'] ?? 0,
            'estado' => 'libre',
            'activo' => true,
        ]);
        $this->registrar($solicitud, 'crear_mesa', 'mesas', $mesa->id_mesa, $datos, $sucursal->id_sucursal);

        return back()->with('exito', 'La mesa fue creada.');
    }

    public function abrirMesa(Request $solicitud, Mesa $mesa): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        abort_unless($mesa->ref_sucursal === $sucursal->id_sucursal && $mesa->activo && $mesa->estado !== 'inactiva', 404);
        $this->autorizar($solicitud, 'ventas.crear', $sucursal->id_sucursal);

        $pedido = DB::transaction(function () use ($mesa, $sucursal, $solicitud): Pedido {
            $pedido = Pedido::query()
                ->where('ref_sucursal', $sucursal->id_sucursal)
                ->where('ref_mesa', $mesa->id_mesa)
                ->where('estado', 'abierto')
                ->where('activo', true)
                ->first();

            if ($pedido) {
                return $pedido;
            }

            $pedido = Pedido::query()->create([
                'ref_sucursal' => $sucursal->id_sucursal,
                'ref_mesa' => $mesa->id_mesa,
                'ref_usuario_apertura' => $solicitud->user()->id_usuario,
                'tipo_servicio' => 'mesa',
                'estado' => 'abierto',
                'abierto_en' => now(),
                'activo' => true,
            ]);
            $mesa->update(['estado' => 'ocupada']);
            $this->crearRondaBorrador($pedido, $solicitud->user()->id_usuario);

            return $pedido;
        });

        $this->registrar($solicitud, 'abrir_mesa', 'pedidos', $pedido->id_pedido, ['ref_mesa' => $mesa->id_mesa], $sucursal->id_sucursal);

        return redirect()->route('operacion', ['ref_pedido' => $pedido->id_pedido]);
    }

    public function crearPedidoMostrador(Request $solicitud): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'ventas.crear', $sucursal->id_sucursal);

        $pedido = DB::transaction(function () use ($sucursal, $solicitud): Pedido {
            $pedido = Pedido::query()->create([
                'ref_sucursal' => $sucursal->id_sucursal,
                'ref_usuario_apertura' => $solicitud->user()->id_usuario,
                'tipo_servicio' => 'mostrador',
                'estado' => 'abierto',
                'referencia_mostrador' => 'Mostrador',
                'abierto_en' => now(),
                'activo' => true,
            ]);
            $this->crearRondaBorrador($pedido, $solicitud->user()->id_usuario);

            return $pedido;
        });

        $this->registrar($solicitud, 'abrir_mostrador', 'pedidos', $pedido->id_pedido, [], $sucursal->id_sucursal);

        return redirect()->route('operacion', ['ref_pedido' => $pedido->id_pedido]);
    }

    public function agregarDetalle(Request $solicitud, Pedido $pedido): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->asegurarPedidoAbierto($pedido, $sucursal->id_sucursal);
        $this->autorizar($solicitud, 'ventas.crear', $sucursal->id_sucursal);

        $datos = $solicitud->validate([
            'ref_producto' => ['required', 'integer'],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:999999.999'],
            'opciones_modificadores' => ['nullable', 'array'],
            'opciones_modificadores.*' => ['integer', 'distinct'],
            'nota_preparacion' => ['nullable', 'string', 'max:1000'],
        ]);

        $producto = $this->productoDisponible((int) $datos['ref_producto'], $sucursal->id_sucursal);
        abort_unless($producto, 422);
        $opciones = $this->opcionesValidasProducto($producto->id_producto, $datos['opciones_modificadores'] ?? []);

        DB::transaction(function () use ($pedido, $solicitud, $datos, $producto, $opciones): void {
            $ronda = $this->rondaBorrador($pedido, $solicitud->user()->id_usuario);
            $importe_extras = $opciones->sum('precio_adicional') * $datos['cantidad'];
            $importe_total = ($producto->precio_venta * $datos['cantidad']) + $importe_extras;

            $detalle = DetalleRondaPedido::query()->create([
                'ref_ronda_pedido' => $ronda->id_ronda_pedido,
                'ref_producto' => $producto->id_producto,
                'cantidad' => $datos['cantidad'],
                'nombre_producto' => $producto->nombre,
                'precio_unitario' => $producto->precio_venta,
                'importe_extras' => $importe_extras,
                'importe_total' => $importe_total,
                'nota_preparacion' => $datos['nota_preparacion'] ?? null,
                'estado' => 'activo',
                'activo' => true,
            ]);

            foreach ($opciones as $opcion) {
                DB::table('detalles_rondas_pedidos_modificadores')->insert([
                    'ref_detalle_ronda_pedido' => $detalle->id_detalle_ronda_pedido,
                    'ref_opcion_modificador_producto' => $opcion->id_opcion_modificador_producto,
                    'ref_insumo' => $opcion->ref_insumo,
                    'nombre_opcion' => $opcion->nombre,
                    'tipo_modificacion' => $opcion->tipo_modificacion,
                    'cantidad_insumo' => $opcion->cantidad_insumo,
                    'precio_adicional' => $opcion->precio_adicional,
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }

            $this->recalcularRonda($ronda);
        });

        return back()->with('exito', 'El producto fue agregado al borrador.');
    }

    public function eliminarDetalle(Request $solicitud, DetalleRondaPedido $detalle): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $ronda = RondaPedido::query()->findOrFail($detalle->ref_ronda_pedido);
        $pedido = Pedido::query()->findOrFail($ronda->ref_pedido);
        $this->asegurarPedidoAbierto($pedido, $sucursal->id_sucursal);
        abort_unless($ronda->estado === 'borrador' && $detalle->estado === 'activo', 422);
        $this->autorizar($solicitud, 'ventas.crear', $sucursal->id_sucursal);

        $detalle->update(['estado' => 'cancelado', 'activo' => false]);
        $this->recalcularRonda($ronda);
        $this->registrar($solicitud, 'retirar_detalle_borrador', 'detalles_rondas_pedidos', $detalle->id_detalle_ronda_pedido, [], $sucursal->id_sucursal);

        return back()->with('exito', 'El producto fue retirado del borrador.');
    }

    public function confirmarRonda(Request $solicitud, Pedido $pedido): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->asegurarPedidoAbierto($pedido, $sucursal->id_sucursal);
        $this->autorizar($solicitud, 'ventas.enviar_preparacion', $sucursal->id_sucursal);

        $ronda = DB::transaction(function () use ($pedido, $sucursal, $solicitud): RondaPedido {
            $ronda = $this->rondaBorrador($pedido, $solicitud->user()->id_usuario);
            $detalles = DetalleRondaPedido::query()
                ->where('ref_ronda_pedido', $ronda->id_ronda_pedido)
                ->where('estado', 'activo')
                ->where('activo', true)
                ->get();
            abort_unless($detalles->isNotEmpty(), 422);

            foreach ($detalles as $detalle) {
                $areas = DB::table('sucursales_productos_areas_preparacion')
                    ->join('sucursales_productos', 'sucursales_productos.id_sucursal_producto', '=', 'sucursales_productos_areas_preparacion.ref_sucursal_producto')
                    ->where('sucursales_productos.ref_sucursal', $sucursal->id_sucursal)
                    ->where('sucursales_productos.ref_producto', $detalle->ref_producto)
                    ->where('sucursales_productos_areas_preparacion.activo', true)
                    ->pluck('sucursales_productos_areas_preparacion.ref_area_preparacion');

                foreach ($areas as $id_area) {
                    DB::table('detalles_rondas_pedidos_areas_preparacion')->insert([
                        'ref_detalle_ronda_pedido' => $detalle->id_detalle_ronda_pedido,
                        'ref_area_preparacion' => $id_area,
                        'estado_preparacion' => 'pendiente',
                        'activo' => true,
                        'creado_en' => now(),
                        'actualizado_en' => now(),
                    ]);
                }
            }

            $ronda->update(['estado' => 'enviada', 'enviada_en' => now()]);
            $this->crearRondaBorrador($pedido, $solicitud->user()->id_usuario);

            return $ronda;
        });

        $this->registrar($solicitud, 'confirmar_ronda', 'rondas_pedidos', $ronda->id_ronda_pedido, ['numero_ronda' => $ronda->numero_ronda], $sucursal->id_sucursal);

        return back()->with('exito', "La ronda {$ronda->numero_ronda} fue confirmada.");
    }

    private function presentarPedido(Pedido $pedido): array
    {
        $rondas = RondaPedido::query()
            ->where('ref_pedido', $pedido->id_pedido)
            ->where('activo', true)
            ->orderBy('numero_ronda')
            ->get();
        $ronda_borrador = $rondas->firstWhere('estado', 'borrador');
        $detalles = $ronda_borrador
            ? DetalleRondaPedido::query()->where('ref_ronda_pedido', $ronda_borrador->id_ronda_pedido)->where('estado', 'activo')->where('activo', true)->orderBy('id_detalle_ronda_pedido')->get()
            : collect();
        $modificadores = DB::table('detalles_rondas_pedidos_modificadores')
            ->whereIn('ref_detalle_ronda_pedido', $detalles->pluck('id_detalle_ronda_pedido'))
            ->where('activo', true)
            ->get(['ref_detalle_ronda_pedido', 'nombre_opcion', 'precio_adicional']);

        $detalles = $detalles->map(function (DetalleRondaPedido $detalle) use ($modificadores): array {
            return [
                ...$detalle->toArray(),
                'modificadores' => $modificadores->where('ref_detalle_ronda_pedido', $detalle->id_detalle_ronda_pedido)->values(),
            ];
        });

        return [
            ...$pedido->only(['id_pedido', 'tipo_servicio', 'referencia_mostrador', 'nota']),
            'mesa' => $pedido->ref_mesa ? Mesa::query()->find($pedido->ref_mesa)?->nombre : null,
            'ronda_borrador' => $ronda_borrador?->only(['id_ronda_pedido', 'numero_ronda', 'importe_total']),
            'detalles_borrador' => $detalles,
            'rondas_enviadas' => $rondas->where('estado', 'enviada')->values(),
            'importe_total' => RondaPedido::query()->where('ref_pedido', $pedido->id_pedido)->where('estado', '!=', 'cancelada')->sum('importe_total'),
        ];
    }

    private function pedidoSeleccionado(Request $solicitud, int $id_sucursal): ?Pedido
    {
        $id_pedido = $solicitud->integer('ref_pedido');

        return $id_pedido
            ? Pedido::query()->where('id_pedido', $id_pedido)->where('ref_sucursal', $id_sucursal)->where('estado', 'abierto')->where('activo', true)->firstOrFail()
            : null;
    }

    private function productosDisponibles(int $id_sucursal)
    {
        return DB::table('productos')
            ->join('sucursales_productos', 'sucursales_productos.ref_producto', '=', 'productos.id_producto')
            ->leftJoin('categorias_productos', 'categorias_productos.id_categoria_producto', '=', 'productos.ref_categoria_producto')
            ->leftJoin('sucursales_categorias_productos', function ($union) use ($id_sucursal): void {
                $union->on('sucursales_categorias_productos.ref_categoria_producto', '=', 'categorias_productos.id_categoria_producto')
                    ->where('sucursales_categorias_productos.ref_sucursal', $id_sucursal);
            })
            ->where('sucursales_productos.ref_sucursal', $id_sucursal)
            ->where('sucursales_productos.habilitado', true)
            ->where('sucursales_productos.activo', true)
            ->where('productos.activo', true)
            ->where(fn ($consulta) => $consulta->whereNull('categorias_productos.id_categoria_producto')->orWhere('sucursales_categorias_productos.habilitada', true))
            ->orderBy('categorias_productos.orden')
            ->orderBy('productos.nombre')
            ->get(['productos.id_producto', 'productos.nombre', 'productos.precio_venta', 'categorias_productos.nombre as categoria']);
    }

    private function productoDisponible(int $id_producto, int $id_sucursal): ?object
    {
        return $this->productosDisponibles($id_sucursal)->firstWhere('id_producto', $id_producto);
    }

    private function opcionesValidasProducto(int $id_producto, array $ids_opciones)
    {
        $ids_opciones = array_values(array_unique($ids_opciones));
        $grupos = DB::table('grupos_modificadores_productos')->where('ref_producto', $id_producto)->where('activo', true)->get();
        $opciones = DB::table('opciones_modificadores_productos')
            ->join('grupos_modificadores_productos', 'grupos_modificadores_productos.id_grupo_modificador_producto', '=', 'opciones_modificadores_productos.ref_grupo_modificador_producto')
            ->where('grupos_modificadores_productos.ref_producto', $id_producto)
            ->where('grupos_modificadores_productos.activo', true)
            ->where('opciones_modificadores_productos.activo', true)
            ->whereIn('opciones_modificadores_productos.id_opcion_modificador_producto', $ids_opciones)
            ->select('opciones_modificadores_productos.*')
            ->get();
        abort_unless($opciones->count() === count($ids_opciones), 422);

        foreach ($grupos as $grupo) {
            $cantidad = $opciones->where('ref_grupo_modificador_producto', $grupo->id_grupo_modificador_producto)->count();
            abort_unless($cantidad >= $grupo->minimo_selecciones && $cantidad <= $grupo->maximo_selecciones, 422);
        }

        return $opciones;
    }

    private function rondaBorrador(Pedido $pedido, int $id_usuario): RondaPedido
    {
        return RondaPedido::query()
            ->where('ref_pedido', $pedido->id_pedido)
            ->where('estado', 'borrador')
            ->where('activo', true)
            ->first() ?? $this->crearRondaBorrador($pedido, $id_usuario);
    }

    private function crearRondaBorrador(Pedido $pedido, int $id_usuario): RondaPedido
    {
        return RondaPedido::query()->create([
            'ref_pedido' => $pedido->id_pedido,
            'ref_usuario_creacion' => $id_usuario,
            'numero_ronda' => ((int) RondaPedido::query()->where('ref_pedido', $pedido->id_pedido)->max('numero_ronda')) + 1,
            'estado' => 'borrador',
            'importe_total' => 0,
            'activo' => true,
        ]);
    }

    private function recalcularRonda(RondaPedido $ronda): void
    {
        $ronda->update(['importe_total' => DetalleRondaPedido::query()->where('ref_ronda_pedido', $ronda->id_ronda_pedido)->where('estado', 'activo')->where('activo', true)->sum('importe_total')]);
    }

    private function asegurarPedidoAbierto(Pedido $pedido, int $id_sucursal): void
    {
        abort_unless($pedido->ref_sucursal === $id_sucursal && $pedido->estado === 'abierto' && $pedido->activo, 404);
    }

    private function sucursalesUsuario(Request $solicitud)
    {
        return Sucursal::query()
            ->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)
            ->where('usuarios_sucursales.activo', true)
            ->where('sucursales.activo', true)
            ->orderByDesc('usuarios_sucursales.es_principal')
            ->orderBy('sucursales.nombre')
            ->get(['sucursales.id_sucursal', 'sucursales.nombre', 'sucursales.clave']);
    }

    private function sucursalSeleccionada(Request $solicitud): Sucursal
    {
        $id_sucursal = $solicitud->integer('ref_sucursal');

        return Sucursal::query()
            ->join('usuarios_sucursales', 'usuarios_sucursales.ref_sucursal', '=', 'sucursales.id_sucursal')
            ->where('usuarios_sucursales.ref_usuario', $solicitud->user()->id_usuario)
            ->where('usuarios_sucursales.activo', true)
            ->where('sucursales.activo', true)
            ->when($id_sucursal, fn ($consulta) => $consulta->where('sucursales.id_sucursal', $id_sucursal))
            ->orderByDesc('usuarios_sucursales.es_principal')
            ->select('sucursales.*')
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
            'modulo' => 'ventas',
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
