<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AutenticacionControlador extends Controller
{
    public function crear(): Response|RedirectResponse
    {
        if (! Negocio::query()->exists()) {
            return redirect()->route('configuracion-inicial');
        }

        if (Auth::check()) {
            return redirect()->route('panel');
        }

        return Inertia::render('Acceso');
    }

    public function iniciar(Request $solicitud): RedirectResponse
    {
        $datos = $solicitud->validate([
            'nombre_usuario' => ['required', 'string'],
            'contrasena' => ['required', 'string'],
            'recordar' => ['nullable', 'boolean'],
        ]);

        $acceso_correcto = Auth::attempt([
            'nombre_usuario' => mb_strtolower($datos['nombre_usuario']),
            'password' => $datos['contrasena'],
            'activo' => true,
        ], $datos['recordar'] ?? false);

        if (! $acceso_correcto) {
            return back()->withErrors(['nombre_usuario' => 'Usuario o contraseña incorrectos.'])->onlyInput('nombre_usuario');
        }

        $solicitud->session()->regenerate();

        return redirect()->intended(route('panel'));
    }

    public function destruir(Request $solicitud): RedirectResponse
    {
        Auth::logout();
        $solicitud->session()->invalidate();
        $solicitud->session()->regenerateToken();

        return redirect()->route('login');
    }
}
