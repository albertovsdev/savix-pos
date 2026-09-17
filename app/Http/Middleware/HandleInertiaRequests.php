<?php

namespace App\Http\Middleware;

use App\Models\Negocio;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'autenticacion' => [
                'usuario' => fn () => $request->user()?->only(['id_usuario', 'nombre', 'nombre_usuario']),
            ],
            'apariencia' => function (): ?array {
                $negocio = Negocio::query()->first(['color_primario', 'color_secundario', 'color_acento', 'tema_predeterminado']);

                return $negocio?->toArray();
            },
            'flash' => [
                'exito' => fn () => $request->session()->get('exito'),
            ],
        ];
    }
}
