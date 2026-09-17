<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionInicialControlador extends Controller
{
    public function crear(): Response|RedirectResponse
    {
        if (Negocio::query()->exists()) {
            return redirect()->route('login');
        }

        return Inertia::render('ConfiguracionInicial');
    }

    public function guardar(Request $solicitud): RedirectResponse
    {
        if (Negocio::query()->exists()) {
            return redirect()->route('login');
        }

        $datos = $solicitud->validate([
            'nombre_negocio' => ['required', 'string', 'max:120'],
            'nombre_comercial' => ['required', 'string', 'max:120'],
            'clave_folio' => ['required', 'alpha_dash', 'min:2', 'max:12'],
            'nombre_sucursal' => ['required', 'string', 'max:120'],
            'clave_sucursal' => ['required', 'alpha_dash', 'min:2', 'max:12'],
            'nombre_admin' => ['required', 'string', 'max:120'],
            'nombre_usuario' => ['required', 'regex:/^[a-zA-Z0-9._-]+$/', 'min:3', 'max:80', 'unique:usuarios,nombre_usuario'],
            'correo' => ['nullable', 'email', 'max:191', 'unique:usuarios,correo'],
            'contrasena' => ['required', 'string', 'min:12', 'confirmed'],
            'pin' => ['required', 'digits:4', 'confirmed'],
        ]);

        $usuario = DB::transaction(function () use ($datos, $solicitud): Usuario {
            $negocio = Negocio::query()->create([
                'nombre' => $datos['nombre_negocio'],
                'nombre_comercial' => $datos['nombre_comercial'],
                'clave_folio' => mb_strtoupper($datos['clave_folio']),
                'tema_predeterminado' => 'oscuro',
                'activo' => true,
            ]);

            $sucursal = Sucursal::query()->create([
                'ref_negocio' => $negocio->id_negocio,
                'clave' => mb_strtoupper($datos['clave_sucursal']),
                'nombre' => $datos['nombre_sucursal'],
                'activo' => true,
            ]);

            $usuario = Usuario::query()->create([
                'nombre' => $datos['nombre_admin'],
                'nombre_usuario' => mb_strtolower($datos['nombre_usuario']),
                'correo' => $datos['correo'] ?: null,
                'contrasena' => $datos['contrasena'],
                'pin_hash' => Hash::make($datos['pin']),
                'activo' => true,
            ]);

            DB::table('usuarios_sucursales')->insert([
                'ref_usuario' => $usuario->id_usuario,
                'ref_sucursal' => $sucursal->id_sucursal,
                'es_principal' => true,
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            $id_rol_admin = DB::table('cat_roles')->where('codigo', 'admin')->value('id_rol');

            DB::table('usuarios_roles')->insert([
                'ref_usuario' => $usuario->id_usuario,
                'ref_rol' => $id_rol_admin,
                'ref_sucursal' => null,
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            foreach (DB::table('cat_modulos')->where('activo', true)->get(['id_modulo']) as $modulo) {
                DB::table('sucursales_modulos')->insert([
                    'ref_sucursal' => $sucursal->id_sucursal,
                    'ref_modulo' => $modulo->id_modulo,
                    'habilitado' => true,
                    'activo' => true,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }

            DB::table('bitacora_auditoria')->insert([
                'ref_usuario' => $usuario->id_usuario,
                'ref_sucursal' => $sucursal->id_sucursal,
                'modulo' => 'configuracion_inicial',
                'accion' => 'crear_instalacion',
                'entidad' => 'negocios',
                'ref_entidad' => $negocio->id_negocio,
                'motivo' => 'Configuración inicial de la instalación.',
                'direccion_ip' => $solicitud->ip(),
                'agente_usuario' => $solicitud->userAgent(),
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            return $usuario;
        });

        Auth::login($usuario);
        $solicitud->session()->regenerate();

        return redirect()->route('panel');
    }
}
