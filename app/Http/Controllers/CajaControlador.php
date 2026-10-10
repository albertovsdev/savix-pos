<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Sucursal;
use App\Services\ServicioPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CajaControlador extends Controller
{
    public function __construct(private readonly ServicioPermisos $servicio_permisos)
    {
    }

    public function mostrar(Request $solicitud): Response
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'caja.cobrar', $sucursal->id_sucursal);
        $pedido = $this->pedidoSeleccionado($solicitud, $sucursal->id_sucursal);

        return Inertia::render('Caja', [
            'sucursal_seleccionada' => $sucursal->only(['id_sucursal', 'nombre', 'clave']),
            'sucursales' => $this->sucursalesUsuario($solicitud),
            'pedidos_abiertos' => $this->pedidosAbiertos($sucursal->id_sucursal),
            'pedido' => $pedido ? $this->datosCuenta($pedido) : null,
        ]);
    }

    public function crearParte(Request $solicitud, Pedido $pedido): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->asegurarPedidoAbierto($pedido, $sucursal->id_sucursal);
        $this->autorizar($solicitud, 'caja.cobrar', $sucursal->id_sucursal);
        $datos = $solicitud->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('partes_cuenta_pedidos')->where('ref_pedido', $pedido->id_pedido)],
        ]);

        $id_parte = DB::table('partes_cuenta_pedidos')->insertGetId([
            'ref_pedido' => $pedido->id_pedido,
            'nombre' => $datos['nombre'],
            'orden' => ((int) DB::table('partes_cuenta_pedidos')->where('ref_pedido', $pedido->id_pedido)->max('orden')) + 1,
            'estado' => 'abierta',
            'activo' => true,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
        $this->registrar($solicitud, 'crear_parte_cuenta', 'partes_cuenta_pedidos', $id_parte, $datos, $sucursal->id_sucursal);

        return back()->with('exito', 'La parte de cuenta fue creada.');
    }

    public function asignarDetalleParte(Request $solicitud, int $id_detalle): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'caja.cobrar', $sucursal->id_sucursal);
        $datos = $solicitud->validate(['ref_parte_cuenta_pedido' => ['required', 'integer']]);

        $detalle = DB::table('detalles_rondas_pedidos')
            ->join('rondas_pedidos', 'rondas_pedidos.id_ronda_pedido', '=', 'detalles_rondas_pedidos.ref_ronda_pedido')
            ->join('pedidos', 'pedidos.id_pedido', '=', 'rondas_pedidos.ref_pedido')
            ->where('detalles_rondas_pedidos.id_detalle_ronda_pedido', $id_detalle)
            ->where('pedidos.ref_sucursal', $sucursal->id_sucursal)
            ->where('pedidos.estado', 'abierto')
            ->where('detalles_rondas_pedidos.estado', 'activo')
            ->where('detalles_rondas_pedidos.activo', true)
            ->select(['detalles_rondas_pedidos.ref_parte_cuenta_pedido', 'pedidos.id_pedido'])
            ->first();
        abort_unless($detalle, 404);
        $parte_destino = DB::table('partes_cuenta_pedidos')
            ->where('id_parte_cuenta_pedido', $datos['ref_parte_cuenta_pedido'])
            ->where('ref_pedido', $detalle->id_pedido)
            ->where('estado', 'abierta')
            ->where('activo', true)
            ->first();
        abort_unless($parte_destino, 422);
        abort_unless(! DB::table('partes_cuenta_pedidos')->where('id_parte_cuenta_pedido', $detalle->ref_parte_cuenta_pedido)->where('estado', 'pagada')->exists(), 422);

        DB::table('detalles_rondas_pedidos')->where('id_detalle_ronda_pedido', $id_detalle)->update([
            'ref_parte_cuenta_pedido' => $parte_destino->id_parte_cuenta_pedido,
            'actualizado_en' => now(),
        ]);
        $this->registrar($solicitud, 'asignar_detalle_parte_cuenta', 'detalles_rondas_pedidos', $id_detalle, $datos, $sucursal->id_sucursal);

        return back()->with('exito', 'El producto fue asignado a la parte seleccionada.');
    }

    public function cobrarParte(Request $solicitud, int $id_parte): RedirectResponse
    {
        $sucursal = $this->sucursalSeleccionada($solicitud);
        $this->autorizar($solicitud, 'caja.cobrar', $sucursal->id_sucursal);
        $datos = $solicitud->validate([
            'pagos' => ['required', 'array', 'min:1'],
            'pagos.*.tipo_pago' => ['required', Rule::in(['efectivo', 'tarjeta'])],
            'pagos.*.importe' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'pagos.*.referencia' => ['nullable', 'string', 'max:120'],
        ]);

        $resultado = DB::transaction(function () use ($id_parte, $datos, $sucursal, $solicitud): array {
            $parte = DB::table('partes_cuenta_pedidos')->where('id_parte_cuenta_pedido', $id_parte)->lockForUpdate()->first();
            abort_unless($parte && $parte->estado === 'abierta' && $parte->activo, 422);
            $pedido = DB::table('pedidos')->where('id_pedido', $parte->ref_pedido)->lockForUpdate()->first();
            abort_unless($pedido && $pedido->ref_sucursal === $sucursal->id_sucursal && $pedido->estado === 'abierto' && $pedido->activo, 404);
            $hay_borrador = DB::table('rondas_pedidos')
                ->join('detalles_rondas_pedidos', 'detalles_rondas_pedidos.ref_ronda_pedido', '=', 'rondas_pedidos.id_ronda_pedido')
                ->where('rondas_pedidos.ref_pedido', $pedido->id_pedido)
                ->where('rondas_pedidos.estado', 'borrador')
                ->where('detalles_rondas_pedidos.estado', 'activo')
                ->where('detalles_rondas_pedidos.activo', true)
                ->exists();
            abort_unless(! $hay_borrador, 422);
            $importe_parte = DB::table('detalles_rondas_pedidos')->where('ref_parte_cuenta_pedido', $parte->id_parte_cuenta_pedido)->where('estado', 'activo')->where('activo', true)->sum('importe_total');
            $centavos_parte = (int) round($importe_parte * 100);
            $centavos_pagos = collect($datos['pagos'])->sum(fn (array $pago) => (int) round(((float) $pago['importe']) * 100));
            if ($centavos_parte <= 0) {
                throw ValidationException::withMessages(['pagos' => 'Esta parte no tiene productos por cobrar.']);
            }
            if ($centavos_parte !== $centavos_pagos) {
                throw ValidationException::withMessages(['pagos' => 'El total capturado debe coincidir exactamente con el importe de la parte.']);
            }

            foreach ($datos['pagos'] as $pago) {
                DB::table('pagos_partes_cuenta_pedidos')->insert([
                    'ref_parte_cuenta_pedido' => $parte->id_parte_cuenta_pedido,
                    'ref_usuario_registro' => $solicitud->user()->id_usuario,
                    'tipo_pago' => $pago['tipo_pago'],
                    'importe' => $pago['importe'],
                    'referencia' => $pago['referencia'] ?? null,
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }
            DB::table('partes_cuenta_pedidos')->where('id_parte_cuenta_pedido', $parte->id_parte_cuenta_pedido)->update([
                'estado' => 'pagada', 'pagada_en' => now(), 'actualizado_en' => now(),
            ]);
            $this->marcarPartesSinConsumoComoPagadas($pedido->id_pedido);

            $hay_partes_abiertas = DB::table('partes_cuenta_pedidos')->where('ref_pedido', $pedido->id_pedido)->where('activo', true)->where('estado', 'abierta')->exists();
            if ($hay_partes_abiertas) {
                return ['cerrado' => false, 'pedido' => $pedido];
            }

            $sucursal_bloqueada = Sucursal::query()->lockForUpdate()->findOrFail($sucursal->id_sucursal);
            $negocio = DB::table('negocios')->first(['clave_folio']);
            $siguiente_folio = $sucursal_bloqueada->ultimo_folio_ticket + 1;
            $folio = mb_strtoupper($negocio->clave_folio.'-'.$sucursal_bloqueada->clave.'-'.str_pad((string) $siguiente_folio, 4, '0', STR_PAD_LEFT));
            $sucursal_bloqueada->update(['ultimo_folio_ticket' => $siguiente_folio]);
            DB::table('pedidos')->where('id_pedido', $pedido->id_pedido)->update([
                'estado' => 'cerrado', 'cerrado_en' => now(), 'folio_ticket' => $folio, 'emitido_ticket_en' => now(), 'actualizado_en' => now(),
            ]);
            if ($pedido->ref_mesa) {
                DB::table('mesas')->where('id_mesa', $pedido->ref_mesa)->update(['estado' => 'libre', 'actualizado_en' => now()]);
            }

            return ['cerrado' => true, 'pedido' => $pedido];
        });

        $this->registrar($solicitud, 'cobrar_parte_cuenta', 'partes_cuenta_pedidos', $id_parte, ['pagos' => $datos['pagos']], $sucursal->id_sucursal);

        return $resultado['cerrado']
            ? redirect()->route('tickets.pedidos.mostrar', $resultado['pedido']->id_pedido)
            : back()->with('exito', 'La parte fue cobrada. La cuenta continúa abierta.');
    }

    public function mostrarTicket(Request $solicitud, Pedido $pedido)
    {
        abort_unless(DB::table('usuarios_sucursales')->where('ref_usuario', $solicitud->user()->id_usuario)->where('ref_sucursal', $pedido->ref_sucursal)->where('activo', true)->exists(), 403);
        abort_unless(
            $this->servicio_permisos->tiene($solicitud->user(), 'ventas.ver', $pedido->ref_sucursal)
            || $this->servicio_permisos->tiene($solicitud->user(), 'caja.cobrar', $pedido->ref_sucursal),
            403,
        );
        abort_unless($pedido->estado === 'cerrado' && $pedido->folio_ticket, 404);

        return view('tickets.pedido', $this->datosTicket($pedido));
    }

    private function pedidosAbiertos(int $id_sucursal): Collection
    {
        return DB::table('pedidos')
            ->leftJoin('mesas', 'mesas.id_mesa', '=', 'pedidos.ref_mesa')
            ->where('pedidos.ref_sucursal', $id_sucursal)
            ->where('pedidos.estado', 'abierto')
            ->where('pedidos.activo', true)
            ->orderBy('pedidos.abierto_en')
            ->get(['pedidos.id_pedido', 'pedidos.tipo_servicio', 'pedidos.referencia_mostrador', 'mesas.nombre as mesa'])
            ->map(fn (object $pedido) => [
                ...get_object_vars($pedido),
                'importe_total' => DB::table('rondas_pedidos')
                    ->join('detalles_rondas_pedidos', 'detalles_rondas_pedidos.ref_ronda_pedido', '=', 'rondas_pedidos.id_ronda_pedido')
                    ->where('rondas_pedidos.ref_pedido', $pedido->id_pedido)
                    ->where('detalles_rondas_pedidos.estado', 'activo')
                    ->where('detalles_rondas_pedidos.activo', true)
                    ->sum('detalles_rondas_pedidos.importe_total'),
            ]);
    }

    private function datosCuenta(Pedido $pedido): array
    {
        $detalles = DB::table('detalles_rondas_pedidos')
            ->join('rondas_pedidos', 'rondas_pedidos.id_ronda_pedido', '=', 'detalles_rondas_pedidos.ref_ronda_pedido')
            ->where('rondas_pedidos.ref_pedido', $pedido->id_pedido)
            ->where('detalles_rondas_pedidos.estado', 'activo')
            ->where('detalles_rondas_pedidos.activo', true)
            ->orderBy('rondas_pedidos.numero_ronda')
            ->orderBy('detalles_rondas_pedidos.id_detalle_ronda_pedido')
            ->get([
                'detalles_rondas_pedidos.id_detalle_ronda_pedido', 'detalles_rondas_pedidos.ref_parte_cuenta_pedido',
                'detalles_rondas_pedidos.cantidad', 'detalles_rondas_pedidos.nombre_producto', 'detalles_rondas_pedidos.importe_total',
                'rondas_pedidos.numero_ronda', 'rondas_pedidos.estado as estado_ronda',
            ]);
        $partes = DB::table('partes_cuenta_pedidos')->where('ref_pedido', $pedido->id_pedido)->where('activo', true)->orderBy('orden')->get();
        $pagos = DB::table('pagos_partes_cuenta_pedidos')->whereIn('ref_parte_cuenta_pedido', $partes->pluck('id_parte_cuenta_pedido'))->where('activo', true)->get();

        return [
            'id_pedido' => $pedido->id_pedido,
            'referencia' => $pedido->tipo_servicio === 'mesa' ? DB::table('mesas')->where('id_mesa', $pedido->ref_mesa)->value('nombre') : $pedido->referencia_mostrador,
            'partes' => $partes->map(fn (object $parte) => [
                ...get_object_vars($parte),
                'importe_total' => $detalles->where('ref_parte_cuenta_pedido', $parte->id_parte_cuenta_pedido)->sum('importe_total'),
                'importe_pagado' => $pagos->where('ref_parte_cuenta_pedido', $parte->id_parte_cuenta_pedido)->sum('importe'),
            ])->values(),
            'detalles' => $detalles,
            'importe_total' => $detalles->sum('importe_total'),
        ];
    }

    private function marcarPartesSinConsumoComoPagadas(int $id_pedido): void
    {
        $partes_abiertas = DB::table('partes_cuenta_pedidos')
            ->where('ref_pedido', $id_pedido)
            ->where('activo', true)
            ->where('estado', 'abierta')
            ->lockForUpdate()
            ->get(['id_parte_cuenta_pedido']);

        foreach ($partes_abiertas as $parte_abierta) {
            $importe = DB::table('detalles_rondas_pedidos')
                ->where('ref_parte_cuenta_pedido', $parte_abierta->id_parte_cuenta_pedido)
                ->where('estado', 'activo')
                ->where('activo', true)
                ->sum('importe_total');
            if ((int) round($importe * 100) === 0) {
                DB::table('partes_cuenta_pedidos')->where('id_parte_cuenta_pedido', $parte_abierta->id_parte_cuenta_pedido)->update([
                    'estado' => 'pagada', 'pagada_en' => now(), 'actualizado_en' => now(),
                ]);
            }
        }
    }
    private function datosTicket(Pedido $pedido): array
    {
        $cuenta = $this->datosCuenta($pedido);
        $detalles_por_parte = collect($cuenta['detalles'])->groupBy('ref_parte_cuenta_pedido');
        $pagos = DB::table('pagos_partes_cuenta_pedidos')->whereIn('ref_parte_cuenta_pedido', collect($cuenta['partes'])->pluck('id_parte_cuenta_pedido'))->where('activo', true)->get()->groupBy('ref_parte_cuenta_pedido');

        $negocio = DB::table('negocios')->first(['nombre', 'nombre_comercial', 'rfc', 'telefono', 'ruta_logo', 'ancho_ticket_mm', 'direccion_ticket', 'pie_ticket']);
        $negocio->logo_url = $negocio->ruta_logo ? '/storage/'.ltrim($negocio->ruta_logo, '/') : null;

        return [
            'negocio' => $negocio,
            'pedido' => $pedido,
            'cuenta' => $cuenta,
            'detalles_por_parte' => $detalles_por_parte,
            'pagos_por_parte' => $pagos,
        ];
    }

    private function pedidoSeleccionado(Request $solicitud, int $id_sucursal): ?Pedido
    {
        $id_pedido = $solicitud->integer('ref_pedido');

        return $id_pedido
            ? Pedido::query()->where('id_pedido', $id_pedido)->where('ref_sucursal', $id_sucursal)->where('estado', 'abierto')->where('activo', true)->firstOrFail()
            : null;
    }

    private function asegurarPedidoAbierto(Pedido $pedido, int $id_sucursal): void
    {
        abort_unless($pedido->ref_sucursal === $id_sucursal && $pedido->estado === 'abierto' && $pedido->activo, 404);
    }

    private function sucursalesUsuario(Request $solicitud): Collection
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

    private function registrar(Request $solicitud, string $accion, string $entidad, int $id_entidad, array $datos_nuevos, ?int $id_sucursal): void
    {
        DB::table('bitacora_auditoria')->insert([
            'ref_usuario' => $solicitud->user()->id_usuario,
            'ref_sucursal' => $id_sucursal,
            'modulo' => 'caja',
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
