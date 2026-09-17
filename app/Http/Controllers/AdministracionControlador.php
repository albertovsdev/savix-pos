<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Services\ServicioPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdministracionControlador extends Controller
{
    public function __construct(private readonly ServicioPermisos $servicio_permisos)
    {
    }

    public function mostrar(Request $solicitud): Response
    {
        $negocio = Negocio::query()->firstOrFail();
        $usuario = $solicitud->user();

        abort_unless(
            $this->servicio_permisos->tiene($usuario, 'negocio.ver')
            || $this->servicio_permisos->tiene($usuario, 'usuarios.ver')
            || $this->servicio_permisos->tiene($usuario, 'sucursales.ver'),
            403,
        );

        $sucursales = Sucursal::query()
            ->where('ref_negocio', $negocio->id_negocio)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Sucursal $sucursal) => [
                ...$sucursal->only(['id_sucursal', 'clave', 'nombre', 'telefono', 'correo', 'direccion', 'activo']),
                'modulos' => DB::table('sucursales_modulos')
                    ->join('cat_modulos', 'cat_modulos.id_modulo', '=', 'sucursales_modulos.ref_modulo')
                    ->where('sucursales_modulos.ref_sucursal', $sucursal->id_sucursal)
                    ->where('sucursales_modulos.habilitado', true)
                    ->pluck('cat_modulos.id_modulo')
                    ->map(fn ($id) => (int) $id)
                    ->all(),
            ])
            ->values();

        $usuarios = Usuario::query()
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'nombre_usuario', 'correo', 'activo'])
            ->map(function (Usuario $usuario) {
                $asignacion = DB::table('usuarios_sucursales')
                    ->join('sucursales', 'sucursales.id_sucursal', '=', 'usuarios_sucursales.ref_sucursal')
                    ->where('usuarios_sucursales.ref_usuario', $usuario->id_usuario)
                    ->where('usuarios_sucursales.activo', true)
                    ->orderByDesc('usuarios_sucursales.es_principal')
                    ->select(['sucursales.id_sucursal', 'sucursales.nombre'])
                    ->first();

                return [
                    ...$usuario->toArray(),
                    'sucursal' => $asignacion,
                    'roles' => DB::table('usuarios_roles')
                        ->join('cat_roles', 'cat_roles.id_rol', '=', 'usuarios_roles.ref_rol')
                        ->where('usuarios_roles.ref_usuario', $usuario->id_usuario)
                        ->where('usuarios_roles.activo', true)
                        ->pluck('cat_roles.nombre')
                        ->all(),
                    'permisos_especiales' => DB::table('usuarios_permisos')
                        ->join('cat_permisos', 'cat_permisos.id_permiso', '=', 'usuarios_permisos.ref_permiso')
                        ->where('usuarios_permisos.ref_usuario', $usuario->id_usuario)
                        ->where('usuarios_permisos.activo', true)
                        ->select(['cat_permisos.nombre', 'usuarios_permisos.tipo_asignacion', 'usuarios_permisos.motivo'])
                        ->get(),
                ];
            })
            ->values();

        return Inertia::render('Administracion', [
            'negocio' => [
                ...$negocio->only(['id_negocio', 'nombre', 'nombre_comercial', 'clave_folio', 'rfc', 'correo', 'telefono', 'color_primario', 'color_secundario', 'color_acento', 'tema_predeterminado', 'porcentaje_iva', 'precios_incluyen_iva', 'ancho_ticket_mm', 'direccion_ticket', 'pie_ticket']),
                'logo_url' => $negocio->ruta_logo ? Storage::disk('public')->url($negocio->ruta_logo) : null,
            ],
            'sucursales' => $sucursales,
            'usuarios' => $usuarios,
            'roles' => DB::table('cat_roles')->where('activo', true)->orderBy('nombre')->get(['id_rol', 'codigo', 'nombre', 'descripcion']),
            'permisos' => DB::table('cat_permisos')->where('activo', true)->orderBy('modulo')->orderBy('nombre')->get(['id_permiso', 'modulo', 'codigo', 'nombre']),
            'modulos' => DB::table('cat_modulos')->where('activo', true)->orderBy('orden')->get(['id_modulo', 'codigo', 'nombre']),
            'puede' => [
                'configurar_negocio' => $this->servicio_permisos->tiene($usuario, 'negocio.configurar'),
                'gestionar_sucursales' => $this->servicio_permisos->tiene($usuario, 'sucursales.gestionar'),
                'gestionar_usuarios' => $this->servicio_permisos->tiene($usuario, 'usuarios.gestionar'),
                'gestionar_roles' => $this->servicio_permisos->tiene($usuario, 'roles.gestionar'),
            ],
        ]);
    }

    public function actualizarNegocio(Request $solicitud): RedirectResponse
    {
        $negocio = Negocio::query()->firstOrFail();
        $this->autorizar($solicitud, 'negocio.configurar');

        $datos = $solicitud->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'nombre_comercial' => ['required', 'string', 'max:120'],
            'clave_folio' => ['required', 'alpha_dash', 'min:2', 'max:12'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:191'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'color_primario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_secundario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_acento' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'tema_predeterminado' => ['required', Rule::in(['claro', 'oscuro', 'sistema'])],
            'porcentaje_iva' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'precios_incluyen_iva' => ['required', 'boolean'],
            'ancho_ticket_mm' => ['required', Rule::in([58, 80])],
            'direccion_ticket' => ['nullable', 'string', 'max:2000'],
            'pie_ticket' => ['nullable', 'string', 'max:2000'],
        ]);

        $archivo_logo = $datos['logo'] ?? null;
        unset($datos['logo']);

        if ($archivo_logo) {
            if ($negocio->ruta_logo) {
                Storage::disk('public')->delete($negocio->ruta_logo);
            }

            $datos['ruta_logo'] = $archivo_logo->store('logos', 'public');
        }

        $anteriores = $negocio->only(array_keys($datos));
        $negocio->update([...$datos, 'clave_folio' => mb_strtoupper($datos['clave_folio'])]);
        $this->registrar($solicitud, 'actualizar_configuracion', 'negocios', $negocio->id_negocio, $datos, null, $anteriores);

        return back()->with('exito', 'La configuración del negocio fue actualizada.');
    }

    public function crearSucursal(Request $solicitud): RedirectResponse
    {
        $negocio = Negocio::query()->firstOrFail();
        $this->autorizar($solicitud, 'sucursales.gestionar');

        $datos = $solicitud->validate([
            'clave' => ['required', 'alpha_dash', 'min:2', 'max:12', Rule::unique('sucursales')->where('ref_negocio', $negocio->id_negocio)],
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:191'],
            'direccion' => ['nullable', 'string', 'max:2000'],
            'modulos' => ['required', 'array'],
            'modulos.*' => ['integer', 'exists:cat_modulos,id_modulo'],
        ]);

        $sucursal = DB::transaction(function () use ($datos, $negocio) {
            $sucursal = Sucursal::query()->create([
                ...collect($datos)->except('modulos')->all(),
                'ref_negocio' => $negocio->id_negocio,
                'clave' => mb_strtoupper($datos['clave']),
                'activo' => true,
            ]);

            foreach (DB::table('cat_modulos')->where('activo', true)->pluck('id_modulo') as $id_modulo) {
                DB::table('sucursales_modulos')->insert([
                    'ref_sucursal' => $sucursal->id_sucursal,
                    'ref_modulo' => $id_modulo,
                    'habilitado' => in_array((int) $id_modulo, $datos['modulos'], true),
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }

            foreach (DB::table('categorias_productos')->where('ref_negocio', $negocio->id_negocio)->pluck('id_categoria_producto') as $id_categoria) {
                DB::table('sucursales_categorias_productos')->insert([
                    'ref_sucursal' => $sucursal->id_sucursal,
                    'ref_categoria_producto' => $id_categoria,
                    'habilitada' => true,
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }

            foreach (DB::table('productos')->where('ref_negocio', $negocio->id_negocio)->pluck('id_producto') as $id_producto) {
                DB::table('sucursales_productos')->insert([
                    'ref_sucursal' => $sucursal->id_sucursal,
                    'ref_producto' => $id_producto,
                    'habilitado' => true,
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }

            return $sucursal;
        });

        $this->registrar($solicitud, 'crear_sucursal', 'sucursales', $sucursal->id_sucursal, $datos, $sucursal->id_sucursal);

        return back()->with('exito', 'La sucursal fue creada.');
    }

    public function actualizarSucursal(Request $solicitud, Sucursal $sucursal): RedirectResponse
    {
        $negocio = Negocio::query()->firstOrFail();
        abort_unless($sucursal->ref_negocio === $negocio->id_negocio, 404);
        $this->autorizar($solicitud, 'sucursales.gestionar', $sucursal->id_sucursal);

        $datos = $solicitud->validate([
            'clave' => ['required', 'alpha_dash', 'min:2', 'max:12', Rule::unique('sucursales')->where('ref_negocio', $negocio->id_negocio)->ignore($sucursal->id_sucursal, 'id_sucursal')],
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:191'],
            'direccion' => ['nullable', 'string', 'max:2000'],
            'activo' => ['required', 'boolean'],
            'modulos' => ['required', 'array'],
            'modulos.*' => ['integer', 'exists:cat_modulos,id_modulo'],
        ]);

        DB::transaction(function () use ($datos, $sucursal): void {
            $sucursal->update([
                ...collect($datos)->except('modulos')->all(),
                'clave' => mb_strtoupper($datos['clave']),
            ]);

            foreach (DB::table('cat_modulos')->where('activo', true)->pluck('id_modulo') as $id_modulo) {
                DB::table('sucursales_modulos')
                    ->where('ref_sucursal', $sucursal->id_sucursal)
                    ->where('ref_modulo', $id_modulo)
                    ->update(['habilitado' => in_array((int) $id_modulo, $datos['modulos'], true), 'actualizado_en' => now()]);
            }
        });

        $this->registrar($solicitud, 'actualizar_sucursal', 'sucursales', $sucursal->id_sucursal, $datos, $sucursal->id_sucursal);

        return back()->with('exito', 'La sucursal fue actualizada.');
    }

    public function crearUsuario(Request $solicitud): RedirectResponse
    {
        $negocio = Negocio::query()->firstOrFail();
        $this->autorizar($solicitud, 'usuarios.gestionar');

        $datos = $solicitud->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'nombre_usuario' => ['required', 'regex:/^[a-zA-Z0-9._-]+$/', 'min:3', 'max:80', 'unique:usuarios,nombre_usuario'],
            'correo' => ['nullable', 'email', 'max:191', 'unique:usuarios,correo'],
            'contrasena' => ['required', 'string', 'min:12', 'confirmed'],
            'pin' => ['required', 'digits:4', 'confirmed'],
            'ref_sucursal' => ['required', Rule::exists('sucursales', 'id_sucursal')->where('ref_negocio', $negocio->id_negocio)],
            'ref_rol' => ['required', 'exists:cat_roles,id_rol'],
        ]);

        $usuario = DB::transaction(function () use ($datos): Usuario {
            $codigo_rol = DB::table('cat_roles')->where('id_rol', $datos['ref_rol'])->value('codigo');

            $usuario = Usuario::query()->create([
                'nombre' => $datos['nombre'],
                'nombre_usuario' => mb_strtolower($datos['nombre_usuario']),
                'correo' => $datos['correo'] ?: null,
                'contrasena' => $datos['contrasena'],
                'pin_hash' => Hash::make($datos['pin']),
                'activo' => true,
            ]);

            DB::table('usuarios_sucursales')->insert([
                'ref_usuario' => $usuario->id_usuario,
                'ref_sucursal' => $datos['ref_sucursal'],
                'es_principal' => true,
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            DB::table('usuarios_roles')->insert([
                'ref_usuario' => $usuario->id_usuario,
                'ref_rol' => $datos['ref_rol'],
                'ref_sucursal' => in_array($codigo_rol, ['dev', 'admin'], true) ? null : $datos['ref_sucursal'],
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            return $usuario;
        });

        $this->registrar($solicitud, 'crear_usuario', 'usuarios', $usuario->id_usuario, collect($datos)->except(['contrasena', 'pin'])->all(), $datos['ref_sucursal']);

        return back()->with('exito', 'El usuario fue creado.');
    }

    public function guardarPermisoUsuario(Request $solicitud, Usuario $usuario): RedirectResponse
    {
        $this->autorizar($solicitud, 'roles.gestionar');

        $datos = $solicitud->validate([
            'ref_permiso' => ['required', 'exists:cat_permisos,id_permiso'],
            'tipo_asignacion' => ['required', Rule::in(['permitir', 'denegar'])],
            'ref_sucursal' => ['nullable', 'exists:sucursales,id_sucursal'],
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        DB::table('usuarios_permisos')
            ->where('ref_usuario', $usuario->id_usuario)
            ->where('ref_permiso', $datos['ref_permiso'])
            ->when($datos['ref_sucursal'] === null, fn ($consulta) => $consulta->whereNull('ref_sucursal'), fn ($consulta) => $consulta->where('ref_sucursal', $datos['ref_sucursal']))
            ->delete();

        DB::table('usuarios_permisos')->insert([
            'ref_usuario' => $usuario->id_usuario,
            ...$datos,
            'activo' => true,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $this->registrar($solicitud, 'asignar_permiso_usuario', 'usuarios_permisos', $usuario->id_usuario, $datos, $datos['ref_sucursal']);

        return back()->with('exito', 'El permiso individual fue actualizado.');
    }

    private function autorizar(Request $solicitud, string $permiso, ?int $id_sucursal = null): void
    {
        abort_unless($this->servicio_permisos->tiene($solicitud->user(), $permiso, $id_sucursal), 403);
    }

    private function registrar(Request $solicitud, string $accion, string $entidad, int $id_entidad, array $datos_nuevos, ?int $id_sucursal = null, ?array $datos_anteriores = null): void
    {
        DB::table('bitacora_auditoria')->insert([
            'ref_usuario' => $solicitud->user()->id_usuario,
            'ref_sucursal' => $id_sucursal,
            'modulo' => 'administracion',
            'accion' => $accion,
            'entidad' => $entidad,
            'ref_entidad' => $id_entidad,
            'datos_anteriores' => $datos_anteriores ? json_encode($datos_anteriores) : null,
            'datos_nuevos' => json_encode($datos_nuevos),
            'direccion_ip' => $solicitud->ip(),
            'agente_usuario' => $solicitud->userAgent(),
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
    }
}
