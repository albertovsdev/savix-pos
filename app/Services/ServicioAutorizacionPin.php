<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class ServicioAutorizacionPin
{
    public function esValido(Usuario $usuario, string $pin): bool
    {
        return $usuario->activo
            && filled($usuario->pin_hash)
            && Hash::check($pin, $usuario->pin_hash);
    }
}
